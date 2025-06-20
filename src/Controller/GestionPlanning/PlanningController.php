<?php

namespace App\Controller\GestionPlanning;

use App\Entity\Planning;
use App\Form\PlanningType;
use App\Repository\PlanningRepository;
use App\Service\AppointmentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/planning')]
class PlanningController extends AbstractController
{
    private $appointmentService;
    
    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }
    #[Route('/', name: 'planning_index', methods: ['GET'])]
    public function index(PlanningRepository $planningRepository): Response
    {
        // Assuming getUser() returns the logged in doctor.
        $doctor = $this->getUser();
        $plannings = $planningRepository->findBy(['doctor' => $doctor]);

        return $this->render('GestionPlanning/planning/index.html.twig', [
            'plannings' => $plannings,
        ]);
    }

  
    #[Route('/new', name: 'planning_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
       
        $doctor = $this->getUser();
        $planning = new Planning();
        $planning->setDoctor($doctor);

        $form = $this->createForm(PlanningType::class, $planning);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($planning);
            $em->flush();

            return $this->redirectToRoute('planning_index');
        }

        return $this->render('GestionPlanning/planning/new.html.twig', [
            'planning' => $planning,
            'form' => $form->createView(),
        ]);
    }


    #[Route('/{id<\d+>}/view', name: 'planning_show', methods: ['GET'])]
    public function show(int $id, PlanningRepository $planningRepository): Response
    {
        $planning = $planningRepository->find($id);
    
        if (!$planning) {
            throw $this->createNotFoundException("Planning with ID $id not found.");
        }
    
        return $this->render('GestionPlanning/planning/show.html.twig', [
            'planning' => $planning,
        ]);
    }
    
    

    #[Route('/{id}/edit', name: 'planning_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Planning $planning, EntityManagerInterface $em): Response
    {
        $doctor = $this->getUser();
        if ($planning->getDoctor() !== $doctor) {
            throw $this->createAccessDeniedException('You can only edit your own planning.');
        }

        $form = $this->createForm(PlanningType::class, $planning);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('planning_index');
        }

        return $this->render('GestionPlanning/planning/edit.html.twig', [
            'planning' => $planning,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'planning_delete', methods: ['POST'])]
    public function delete(Request $request, Planning $planning, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $planning->getId(), $request->request->get('_token'))) {
            $em->remove($planning);
            $em->flush();
        }

        return $this->redirectToRoute('planning_index');
    }    
    #[Route('/calendar', name: 'calendar_list', methods: ['GET'])]
    public function listCalendars(PlanningRepository $planningRepository): Response
    {
        // Assuming getUser() gives access to the logged-in doctor
        $doctor = $this->getUser();
        $calendars = $planningRepository->findBy(['doctor' => $doctor]);
    
        return $this->render('GestionPlanning/calendar/index.html.twig', [
            'calendars' => $calendars,
        ]);
    }

    #[Route('/my-appointments', name: 'doctor_appointments', methods: ['GET'])]
    public function doctorAppointments(): Response
    {
        $doctor = $this->getUser();
        if (!$doctor) {
            throw $this->createAccessDeniedException('You must be logged in to view your appointments.');
        }
        
        // Get appointments for this doctor using the service
        $appointments = $this->appointmentService->getDoctorAppointments($doctor);
        
        return $this->render('GestionPlanning/planning/doctor_appointments.html.twig', [
            'appointments' => $appointments,
            'doctor' => $doctor
        ]);
    }


    #[Route('/events.json', name: 'events_list', methods: ['GET'])]
    public function eventsList(PlanningRepository $planningRepository): Response
    {
        $doctor = $this->getUser();
        $plannings = $planningRepository->findBy(['doctor' => $doctor]);

        $events = [];
        foreach ($plannings as $planning) {
            $events[] = [
                'title' => 'Available Time Slot',
                'start' => $planning->getStartDate()->format('Y-m-d\TH:i:s'),
                'end' => $planning->getEndDate()->format('Y-m-d\TH:i:s'),
            ];
        }

        return $this->json($events);
    }
}
