<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\Planning;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\PlanningRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

class AppointmentService
{
    private $em;
    private $appointmentRepository;
    private $planningRepository;
    private $security;
    private $doctorCalendarService;

    public function __construct(
        EntityManagerInterface $em,
        AppointmentRepository $appointmentRepository,
        PlanningRepository $planningRepository,
        Security $security,
        DoctorCalendarService $doctorCalendarService = null
    ) {
        $this->em = $em;
        $this->appointmentRepository = $appointmentRepository;
        $this->planningRepository = $planningRepository;
        $this->security = $security;
        $this->doctorCalendarService = $doctorCalendarService;
    }

    /**
     * Create a new appointment
     */
    public function createAppointment(User $user, Planning $planning, \DateTime $startAt): ?Appointment
    {
        // Validate that the appointment time is within planning hours
        if (!$this->validateAppointmentTime($planning, $startAt)) {
            return null;
        }

        // Check for conflicts
        if ($this->hasTimeConflict($planning, $startAt)) {
            return null;
        }

        $appointment = new Appointment();
        $appointment->setUser($user);
        $appointment->setPlanning($planning);
        $appointment->setStartAt($startAt);

        $this->em->persist($appointment);
        $this->em->flush();

        // Sync with Google Calendar if service exists
        if ($this->doctorCalendarService) {
            $this->doctorCalendarService->syncAppointmentWithGoogleCalendar($appointment);
        }

        return $appointment;
    }

    /**
     * Validate that appointment time is within planning constraints
     */
    public function validateAppointmentTime(Planning $planning, \DateTime $startAt): bool
    {
        // Check if date is within planning date range
        if ($startAt < $planning->getStartDate() || $startAt > $planning->getEndDate()) {
            return false;
        }

        // Extract time part for comparison with daily hours
        $timeOfDay = new \DateTime($startAt->format('Y-m-d H:i:s'));
        $timeOfDay->setDate(1970, 1, 1);
        
        $planningStartTime = new \DateTime($planning->getDailyStartTime()->format('Y-m-d H:i:s'));
        $planningStartTime->setDate(1970, 1, 1);
        
        $planningEndTime = new \DateTime($planning->getDailyEndTime()->format('Y-m-d H:i:s'));
        $planningEndTime->setDate(1970, 1, 1);
        // Check if time is within daily working hours
        if ($timeOfDay < $planningStartTime || $timeOfDay >= $planningEndTime) {
            return false;
        }

        // Check if appointment is in the past
        $now = new \DateTime();
        if ($startAt <= $now) {
            return false;
        }

        return true;
    }

    /**
     * Check for appointment time conflicts
     */
    public function hasTimeConflict(Planning $planning, \DateTime $startAt): bool
    {
        $existing = $this->appointmentRepository->findOneBy([
            'planning' => $planning,
            'startAt' => $startAt,
        ]);

        return $existing !== null;
    }

    /**
     * Get all appointments for a doctor
     */
    public function getDoctorAppointments(User $doctor): array
    {
        // Find all plannings for this doctor
        $plannings = $this->planningRepository->findBy(['doctor' => $doctor]);
        
        if (empty($plannings)) {
            return [];
        }
        
        $appointments = [];
        foreach ($plannings as $planning) {
            $planningAppointments = $this->appointmentRepository->findBy(['planning' => $planning]);
            $appointments = array_merge($appointments, $planningAppointments);
        }
        
        return $appointments;
    }
    
    /**
     * Get available time slots for a planning on a specific date
     */
    public function getAvailableTimeSlots(Planning $planning, \DateTime $date): array
    {
        // Check if date is within planning range
        if ($date < $planning->getStartDate() || $date > $planning->getEndDate()) {
            return [];
        }
        
        // Build time slots based on planning hours (2-hour intervals)
        $dailyStartTime = clone $planning->getDailyStartTime();
        $dailyEndTime = clone $planning->getDailyEndTime();
        
        $timeSlots = [];
        $currentSlot = clone $dailyStartTime;
        
        while ($currentSlot < $dailyEndTime) {
            $slotKey = $currentSlot->format('H:i');
            $timeSlots[$slotKey] = $currentSlot->format('H:i:s');
            $currentSlot = (new \DateTime())->setTimestamp($currentSlot->getTimestamp())->modify('+2 hours');
        }
        
        // Get booked appointments for this date
        $dateFormatted = $date->format('Y-m-d');
        $bookedSlots = [];
        
        $appointments = $this->appointmentRepository->findBy(['planning' => $planning]);
        foreach ($appointments as $appointment) {
            $appointmentDate = $appointment->getStartAt()->format('Y-m-d');
            if ($appointmentDate === $dateFormatted) {
                $bookedSlots[] = $appointment->getStartAt()->format('H:i:s');
            }
        }
        
        // Remove booked slots
        foreach ($bookedSlots as $bookedTime) {
            foreach ($timeSlots as $display => $timeString) {
                if ($timeString === $bookedTime) {
                    unset($timeSlots[$display]);
                }
            }
        }
        
        return $timeSlots;
    }
    public function rescheduleAppointment(Appointment $appointment, \DateTime $newDateTime): void
    {
        // Ensure user has permission (doctor can only change their own appointments)
        $currentUser = $this->security->getUser();
        
        if (!$currentUser || !$this->security->isGranted('ROLE_DOCTOR')) {
            throw new \Exception('You do not have permission to reschedule appointments.');
        }
        
        // Get doctor from planning
        $doctor = $appointment->getPlanning()->getDoctor();
        
        
      
        
        // Calculate end time (30 minute appointments by default)
        $endDateTime = clone $newDateTime;
        $endDateTime->modify('+120 minutes');
        
        // Check if the new date is within the planning period
        $planning = $appointment->getPlanning();
        $planningStartDate = $planning->getStartDate();
        $planningEndDate = $planning->getEndDate();
        
        $newDate = clone $newDateTime;
        $newDate->setTime(0, 0, 0); // Set to midnight for date comparison
        
        if ($newDate < $planningStartDate || $newDate > $planningEndDate) {
            throw new \Exception("The selected date is outside the planning period ({$planningStartDate->format('Y-m-d')} to {$planningEndDate->format('Y-m-d')}).");
        }
        
        // Check if the new time is within daily hours
        $newTimeOnly = $newDateTime->format('H:i:s');
        $dailyStartTime = $planning->getDailyStartTime()->format('H:i:s');
        $dailyEndTime = $planning->getDailyEndTime()->format('H:i:s');
        
        if ($newTimeOnly < $dailyStartTime || $newTimeOnly > $dailyEndTime) {
            throw new \Exception("The selected time is outside the daily working hours ({$dailyStartTime} to {$dailyEndTime}).");
        }

       
        
        // Check for conflicts with other appointments
        $conflictingAppointments = $this->appointmentRepository->findConflictingAppointments(
            $doctor->getId(),
            $newDateTime,
            $endDateTime,
            $appointment->getId()
        );
        
        if (count($conflictingAppointments) > 0) {
            throw new \Exception('The requested time slot conflicts with another appointment.');
        }
        
        // Update the appointment
        $appointment->setStartAt($newDateTime);
        
        // Save changes
        $this->em->persist($appointment);
        $this->em->flush();
    }
}