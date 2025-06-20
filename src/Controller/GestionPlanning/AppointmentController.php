<?php

namespace App\Controller\GestionPlanning;

use App\Entity\Appointment;
use App\Entity\Planning;
use App\Form\GestionPlanning\AppointmentType;
use App\Repository\AppointmentRepository;
use App\Repository\PlanningRepository;
use App\Service\AppointmentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twilio\Rest\Client;


#[Route('/appointment')]
class AppointmentController extends AbstractController
{

    private $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }
    #[Route('/', name: 'appointment_index', methods: ['GET'])]
    public function index(AppointmentRepository $appointmentRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to view your appointments.');
        }

        return $this->render('GestionPlanning/appointment/index.html.twig', [
            'appointments' => $appointmentRepository->findBy(['user' => $user]),
        ]);
    }


    // List available doctors (plannings) for the patient to choose from.
    #[Route('/choose', name: 'appointment_choose', methods: ['GET'])]
    public function choose(PlanningRepository $planningRepository): Response
    {
        // Optionally filter by date or availability.
        $plannings = $planningRepository->findAll();
        return $this->render('GestionPlanning/appointment/choose.html.twig', [
            'plannings' => $plannings,
        ]);
    }

    #[Route('/new/{planningId}', name: 'appointment_new', methods: ['GET', 'POST'])]
    public function addAppointment(
        Request $request,
        EntityManagerInterface $em,
        PlanningRepository $planningRepository,
        AppointmentRepository $appointmentRepository,
        Client $twilioClient,
        int $planningId
    ): Response {
        $planning = $planningRepository->find($planningId);
        if (!$planning) {
            throw $this->createNotFoundException('Planning not found');
        }

        $user = $this->getUser(); // Get logged-in user
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to book an appointment.');
        }

        $appointment = new Appointment();
        $appointment->setUser($user);
        $appointment->setPlanning($planning);

        $form = $this->createForm(AppointmentType::class, $appointment, [
            'planning' => $planning,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $appointmentDate = $form->get('appointmentDate')->getData();
            $timeSlot = $form->get('startAt')->getData();
            $startAt = new \DateTime($appointmentDate->format('Y-m-d') . ' ' . $timeSlot);
    
            // Check if the appointment date is within the planning's start and end date
            if ($startAt < $planning->getStartDate() || $startAt > $planning->getEndDate()) {
                $this->addFlash('error', 'The appointment must be within the planning date range.');
                return $this->redirectToRoute('appointment_new', [
                    'id' => $user->getId(),
                    'planningId' => $planningId,
                ]);
            }

            // Prevent appointment booking in the past or on the same day
            $now = new \DateTime();
            if ($startAt <= $now) {
                $this->addFlash('error', 'You cannot book an appointment in the past or for today.');
                return $this->redirectToRoute('appointment_new', [
                    'id' => $user->getId(),
                    'planningId' => $planningId,
                ]);
            }

            // Check if the time slot is already booked
            $existing = $appointmentRepository->findOneBy([
                'planning' => $planning,
                'startAt' => $startAt,
            ]);

            if ($existing) {
                $this->addFlash('error', 'This time slot is already booked!');
                return $this->redirectToRoute('appointment_new', [
                    'id' => $user->getId(),
                    'planningId' => $planningId,
                ]);
            }

            $appointment->setStartAt($startAt);
            $em->persist($appointment);
            $em->flush();

            try {
                $message = sprintf(
                    'Your appointment with Dr. %s is confirmed for %s.',
                    $planning->getDoctor()->getName(),
                        $appointment->getStartAt()->format('d/m/Y \a\t H:i')
                );
        
                // Format phone number (adjust country code if needed)
                $numeroLocal = $user->getphoneNumber();
                $codePays = '216'; // Tunisia country code
                $to = '+' . $codePays . $numeroLocal;
        
                // Send SMS
                $twilioClient->messages->create(
                    $to,
                    [
                        'from' => $_ENV['TWILIO_PHONE_NUMBER'],
                        'body' => $message,
                    ]
                );
        
                $this->addFlash('success', 'Appointment booked! Confirmation SMS sent.');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Appointment booked, but SMS notification failed.');
            }

            return $this->redirectToRoute('appointment_index', ['id' => $user->getId()]);
        }

        return $this->render('GestionPlanning/appointment/new.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'planning' => $planning
        ]);
    }

    public function new(
        Request $request,
        EntityManagerInterface $em,
        PlanningRepository $planningRepository,
        AppointmentRepository $appointmentRepository,
        Client $twilioClient, // Add Twilio client here
        int $planningId
    ): Response {
        $planning = $planningRepository->find($planningId);
        if (!$planning) {
            throw $this->createNotFoundException('Planning not found');
        }

        $user = $this->getUser(); // Get logged-in user
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to book an appointment.');
        }

        $appointment = new Appointment();
        $appointment->setPlanning($planning);
        $appointment->setUser($user); // Set the user

        $form = $this->createForm(AppointmentType::class, $appointment, ['planning' => $planning]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $appointmentDate = $form->get('appointmentDate')->getData();
            $timeSlot = $form->get('startAt')->getData();        
        
            if (!$appointmentDate || !$timeSlot) {
                $this->addFlash('error', 'Please select a valid appointment date and time.');
                return $this->redirectToRoute('appointment_new', ['planningId' => $planningId]);
            }

            $startAt = new \DateTime($appointmentDate->format('Y-m-d') . ' ' . $timeSlot);


            // Check if the appointment date is within the planning's start and end date
            if ($startAt < $planning->getStartDate() || $startAt > $planning->getEndDate()) {
                $this->addFlash('error', 'The appointment must be within the planning date range.');
                return $this->redirectToRoute('appointment_new', ['planningId' => $planningId]);
            }

            // Prevent appointment booking in the past or on the same day
            $now = new \DateTime();
            if ($startAt <= $now) {
                $this->addFlash('error', 'You cannot book an appointment in the past or for today.');
                return $this->redirectToRoute('appointment_new', ['planningId' => $planningId]);
            }

            $existing = $appointmentRepository->findOneBy([
                'planning' => $planning,
                'startAt' => $startAt,
            ]);

            if ($existing) {
                $this->addFlash('error', 'This time slot is already booked!');
                return $this->redirectToRoute('appointment_new', ['planningId' => $planningId]);
            }

            $appointment->setStartAt($startAt);

            $em->persist($appointment);
            $em->flush();

            try {
                $message = sprintf(
                    'Your appointment with Dr. %s is confirmed for %s.',
                    $planning->getDoctor()->getName(), 
                    $appointment->getStartAt()->format('d/m/Y \a\t H:i')
                );
        
                // Format phone number (adjust country code if needed)
                $numeroLocal = $user->getphoneNumber();
                $codePays = '216'; // Tunisia country code
                $to = '+' . $codePays . $numeroLocal;
        
                // Send SMS
                $twilioClient->messages->create(
                    $to,
                    [
                        'from' => $_ENV['TWILIO_PHONE_NUMBER'],
                        'body' => $message,
                    ]
                );
        
                $this->addFlash('success', 'Appointment booked! Confirmation SMS sent.');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Appointment booked, but SMS notification failed.');
            }
        

            return $this->redirectToRoute('appointment_index');
        }

        return $this->render('GestionPlanning/appointment/new.html.twig', [
            'appointment' => $appointment,
            'form' => $form->createView(),
            'planning' => $planning,
        ]);
    } 
    
  #[Route('/view/{id}', name: 'appointment_show', methods: ['GET'])]
  public function show(Appointment $appointment): Response
  {
      return $this->render('GestionPlanning/appointment/show.html.twig', [
          'appointment' => $appointment,
      ]);
  }

  #[Route('/{id}/edit', name: 'appointment_edit', methods: ['GET', 'POST'])]
  public function edit(Request $request, Appointment $appointment, EntityManagerInterface $em, AppointmentRepository $appointmentRepository): Response
  {
      $form = $this->createForm(AppointmentType::class, $appointment, [
          'planning' => $appointment->getPlanning()
      ]);
      $form->handleRequest($request);

      if ($form->isSubmitted() && $form->isValid()) {
          $appointmentDate = $form->get('appointmentDate')->getData();
          $timeSlot = $form->get('startAt')->getData();

          if (!$appointmentDate || !$timeSlot) {
              $this->addFlash('error', 'Please select a valid date and time slot.');
              return $this->redirectToRoute('appointment_edit', ['id' => $appointment->getId()]);
          }

          $startAt = new \DateTime($appointmentDate->format('Y-m-d') . ' ' . $timeSlot);
          $planning = $appointment->getPlanning();

          // Validate time with service
          if (!$this->appointmentService->validateAppointmentTime($planning, $startAt)) {
              $this->addFlash('error', 'The selected time is not valid for this planning.');
              return $this->redirectToRoute('appointment_edit', ['id' => $appointment->getId()]);
          }

          // Check for conflicts but exclude the current appointment
          $existing = $appointmentRepository->findOneBy([
              'planning' => $planning,
              'startAt' => $startAt,
          ]);

          if ($existing && $existing->getId() !== $appointment->getId()) {
              $this->addFlash('error', 'This time slot is already booked!');
              return $this->redirectToRoute('appointment_edit', ['id' => $appointment->getId()]);
          }

          $appointment->setStartAt($startAt);
          $em->flush();

          $this->addFlash('success', 'Appointment updated successfully!');
          return $this->redirectToRoute('appointment_index');
      }

      return $this->render('GestionPlanning/appointment/edit.html.twig', [
          'appointment' => $appointment,
          'form' => $form->createView(),
      ]);
  }

  #[Route('/{id}/delete', name: 'appointment_delete', methods: ['POST'])]
  public function delete(Request $request, Appointment $appointment, EntityManagerInterface $em): Response
  {
      if ($this->isCsrfTokenValid('delete' . $appointment->getId(), $request->request->get('_token'))) {
          $em->remove($appointment);
          $em->flush();
          $this->addFlash('success', 'Appointment deleted successfully!');
      }

      return $this->redirectToRoute('appointment_index');
  }

  // --- AJAX Endpoint ---
  #[Route('/available-times/{planningId}', name: 'appointment_available_times', methods: ['GET'])]
  public function availableTimes(
      Request $request,
      PlanningRepository $planningRepository,
      int $planningId
  ): Response {
      // Get the date from query parameters (expected format: YYYY-MM-DD)
      $dateParam = $request->query->get('date');
      if (!$dateParam) {
          return $this->json(['error' => 'No date provided'], 400);
      }
      try {
          $dateObj = new \DateTime($dateParam);
      } catch (\Exception $e) {
          return $this->json(['error' => 'Invalid date'], 400);
      }

      // Find the planning
      $planning = $planningRepository->find($planningId);
      if (!$planning) {
          return $this->json(['error' => 'Planning not found'], 404);
      }

      // Use service to get available time slots
      $availableSlots = $this->appointmentService->getAvailableTimeSlots($planning, $dateObj);

      return $this->json($availableSlots);
  }


    // --- New AJAX Endpoint ---


    #[Route('/appointments', name: 'api_appointments', methods: ['GET'])]
    public function getAppointments(EntityManagerInterface $entityManager): JsonResponse
    {
        // Fetch the logged-in doctor (user)
        $doctor = $this->getUser(); // Assuming getUser() gives you the doctor (User)

        // Query appointments related to this doctor (via the 'user' field)
        $appointments = $entityManager->getRepository(Appointment::class)->findBy([
            'user' => $doctor // Filter appointments for the logged-in doctor
        ]);

        // Format the appointments for the calendar
        $events = [];
        foreach ($appointments as $appointment) {
            $start = new \DateTime($appointment->getStartAt()); 
            $end = (clone $start)->add(new \DateInterval('PT1H'));                         
            // Now you can format it as needed
            $events[] = [
                'title' => 'Appointment with ' . $appointment->getUser()->getName(),
                'start' => $start->format('Y-m-d\TH:i:s'), // Date in ISO8601 format
                'end' => $end->format('Y-m-d\TH:i:s'), // Added 1 hour
                'description' => 'Appointment with doctor ' . $appointment->getUser()->getName(),
            ];
        }

        return new JsonResponse($events); // Return the events in JSON format
    }
}
