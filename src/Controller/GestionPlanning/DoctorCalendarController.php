<?php

namespace App\Controller\GestionPlanning;

use App\Service\DoctorCalendarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/doctor/calendar')]
class DoctorCalendarController extends AbstractController
{
    private $doctorCalendarService;
    private $security;

    public function __construct(DoctorCalendarService $doctorCalendarService, Security $security)
    {
        $this->doctorCalendarService = $doctorCalendarService;
        $this->security = $security;
    }

    #[Route('/', name: 'doctor_calendar')]
    public function index(): Response
    {
        $user = $this->getUser();

        return $this->render('GestionPlanning/calendar/doctor_calendar.html.twig', [
            'doctor' => $user,
            'currentDate' => new \DateTime()
        ]);
    }

    #[Route('/events', name: 'doctor_calendar_events', methods: ['GET'])]
    public function getEvents(): JsonResponse
    {
        $user = $this->getUser();

        // Get both availability and appointments
        $availability = $this->doctorCalendarService->getDoctorAvailability($user);
        $appointments = $this->doctorCalendarService->getDoctorAppointments($user);

        // Merge both types of events
        $allEvents = array_merge($availability, $appointments);

        return new JsonResponse($allEvents);
    }
}