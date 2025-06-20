<?php

namespace App\Controller\GestionPlanning;

use App\Entity\Appointment;
use App\Entity\Planning;
use App\Entity\User;
use App\Form\GestionPlanning\AppointmentType;
use App\Repository\AppointmentRepository;
use App\Repository\PlanningRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/appointment')]
class AdminAppointmentController extends AbstractController
{
    #[Route('/', name: 'admin_appointments')]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAllWithAppointments();
        return $this->render('admin/GestionPlanning/appointment/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/choose/{userId}', name: 'admin_appointment_choose', methods: ['GET'])]
    public function choose(PlanningRepository $planningRepository, UserRepository $userRepository, int $userId): Response
    {
        $plannings = $planningRepository->findAll();
        $user = $userRepository->find($userId);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        return $this->render('admin/GestionPlanning/appointment/choose.html.twig', [
            'plannings' => $plannings,
            'user' => $user,
        ]);
    }


    #[Route('/new/{planningId}/{userId}', name: 'admin_appointment_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        PlanningRepository $planningRepository,
        AppointmentRepository $appointmentRepository,
        int $planningId,
        int $userId
    ): Response {
        $planning = $planningRepository->find($planningId);
        $user = $em->getRepository(User::class)->find($userId);

        if (!$planning || !$user) {
            throw $this->createNotFoundException('Planning or User not found');
        }

        $appointment = new Appointment();
        $appointment->setPlanning($planning);
        $appointment->setUser($user);

        $form = $this->createForm(AppointmentType::class, $appointment, [
            'planning' => $planning,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $appointmentDate = $form->get('appointmentDate')->getData();
            $timeSlot = $form->get('startAt')->getData();

            if (!$appointmentDate || !$timeSlot) {
                $this->addFlash('error', 'Please select a valid date and time slot.');
                return $this->redirectToRoute('admin_appointment_new', [
                    'userId' => $userId,
                    'planningId' => $planningId,
                ]);
            }

            $startAt = new \DateTime($appointmentDate->format('Y-m-d') . ' ' . $timeSlot);



            // Check if the appointment date is within the planning's start and end date
            if ($startAt < $planning->getStartDate() || $startAt > $planning->getEndDate()) {
                $this->addFlash('error', 'The appointment must be within the planning date range.');
                return $this->redirectToRoute('admin_appointment_new', [
                    'userId' => $userId,
                    'planningId' => $planningId,
                ]);
            }

            // Prevent appointment booking in the past or on the same day
            $now = new \DateTime();
            if ($startAt <= $now) {
                $this->addFlash('error', 'You cannot book an appointment in the past or for today.');
                return $this->redirectToRoute('admin_appointment_new', [
                    'userId' => $userId,
                    'planningId' => $planningId,
                ]);
            }

            $existing = $appointmentRepository->findOneBy([
                'planning' => $planning,
                'startAt' => $startAt,
            ]);

            if ($existing) {
                $this->addFlash('error', 'This time slot is already booked!');
                return $this->redirectToRoute('admin_appointment_new', ['planningId' => $planningId, 'userId' => $userId]);
            }

            $appointment->setStartAt($startAt);
            $em->persist($appointment);
            $em->flush();

            return $this->redirectToRoute('admin_appointments');
        }

        return $this->render('admin/GestionPlanning/appointment/new.html.twig', [
            'appointment' => $appointment,
            'form' => $form->createView(),
            'planning' => $planning,
            'user' => $user,
        ]);
    }

    #[Route('/admin/user/{id}/appointments', name: 'admin_user_appointments')]
    public function userAppointments(User $user, AppointmentRepository $appointmentRepository): Response
    {
        $appointments = $appointmentRepository->findBy(['user' => $user]);

        return $this->render('admin/GestionPlanning/appointment/user_appointments.html.twig', [
            'user' => $user,
            'appointments' => $appointments,
        ]);
    }


    #[Route('/admin/user/{id}/appointment/choose-planning', name: 'admin_appointment_choose_planning')]
    public function choosePlanning(User $user, PlanningRepository $planningRepository): Response
    {
        $plannings = $planningRepository->findAll();
        return $this->render('admin/GestionPlanning/appointment/choose.html.twig', [
            'user' => $user,
            'plannings' => $plannings,
        ]);
    }

    #[Route('/admin/user/{id}/appointment/add/{planningId}', name: 'admin_appointment_add')]
    public function addAppointment(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        PlanningRepository $planningRepository,
        AppointmentRepository $appointmentRepository,
        int $planningId
    ): Response {
        $planning = $planningRepository->find($planningId);

        if (!$planning) {
            throw $this->createNotFoundException('Planning not found');
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
                return $this->redirectToRoute('admin_appointment_add', [
                    'id' => $user->getId(),
                    'planningId' => $planningId,
                ]);
            }

            // Prevent appointment booking in the past or on the same day
            $now = new \DateTime();
            if ($startAt <= $now) {
                $this->addFlash('error', 'You cannot book an appointment in the past or for today.');
                return $this->redirectToRoute('admin_appointment_add', [
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
                return $this->redirectToRoute('admin_appointment_add', [
                    'id' => $user->getId(),
                    'planningId' => $planningId,
                ]);
            }

            $appointment->setStartAt($startAt);
            $em->persist($appointment);
            $em->flush();

            return $this->redirectToRoute('admin_user_appointments', ['id' => $user->getId()]);
        }

        return $this->render('admin/GestionPlanning/appointment/new.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'planning' => $planning
        ]);
    }


    #[Route('/edit/{id}', name: 'admin_appointment_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Appointment $appointment,
        EntityManagerInterface $em,
        AppointmentRepository $appointmentRepository
    ): Response {
        $form = $this->createForm(AppointmentType::class, $appointment, [
            'planning' => $appointment->getPlanning(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $appointmentDate = $form->get('appointmentDate')->getData();
            $timeSlot = $form->get('startAt')->getData();

            if (!$appointmentDate || !$timeSlot) {
                $this->addFlash('error', 'Please select a valid date and time slot.');
                return $this->redirectToRoute('admin_appointment_edit', ['id' => $appointment->getId()]);
            }

            $startAt = new \DateTime($appointmentDate->format('Y-m-d') . ' ' . $timeSlot);


            $planning = $appointment->getPlanning();

            // Ensure the appointment is within the planning's date range
            if ($startAt < $planning->getStartDate() || $startAt > $planning->getEndDate()) {
                $this->addFlash('error', 'The appointment must be within the planning date range.');
                return $this->redirectToRoute('admin_appointment_edit', ['id' => $appointment->getId()]);
            }

            // Prevent booking in the past or on the same day
            $now = new \DateTime();
            if ($startAt <= $now) {
                $this->addFlash('error', 'You cannot set an appointment in the past or for today.');
                return $this->redirectToRoute('admin_appointment_edit', ['id' => $appointment->getId()]);
            }

            // Check if the time slot is already booked
            $existing = $appointmentRepository->findOneBy([
                'planning' => $appointment->getPlanning(),
                'startAt' => $startAt,
            ]);

            if ($existing && $existing->getId() !== $appointment->getId()) {
                $this->addFlash('error', 'This time slot is already booked!');
                return $this->redirectToRoute('admin_appointment_edit', ['id' => $appointment->getId()]);
            }

            $appointment->setStartAt($startAt);
            $em->flush();

            return $this->redirectToRoute('admin_appointments');
        }

        return $this->render('admin/GestionPlanning/appointment/edit.html.twig', [
            'form' => $form->createView(),
            'appointment' => $appointment,
        ]);
    }


    #[Route('/delete/{id}', name: 'admin_appointment_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Appointment $appointment,
        EntityManagerInterface $em
    ): Response {
        $referer = $request->headers->get('referer');

        if ($this->isCsrfTokenValid('delete' . $appointment->getId(), $request->request->get('_token'))) {
            $em->remove($appointment);
            $em->flush();
            $this->addFlash('success', 'Appointment deleted successfully!');
        } else {
            $this->addFlash('error', 'Invalid CSRF token!');
        }



        // Redirect based on the referer
        if ($referer && str_contains($referer, '/admin/planning/')) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('admin_appointments');
    }



}
