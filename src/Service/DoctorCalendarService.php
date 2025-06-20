<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Planning;
use App\Entity\Appointment;
use App\Repository\AppointmentRepository;
use App\Repository\PlanningRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class DoctorCalendarService
{
    private $entityManager;
    private $appointmentRepository;
    private $planningRepository;
    private $calendarService;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        AppointmentRepository $appointmentRepository,
        PlanningRepository $planningRepository,
        CalendarService $calendarService,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->appointmentRepository = $appointmentRepository;
        $this->planningRepository = $planningRepository;
        $this->calendarService = $calendarService;
        $this->logger = $logger;
    }

    /**
     * Get all appointments for a doctor
     * 
     * @param User $doctor
     * @return array
     */
    public function getDoctorAppointments(User $doctor): array
    {
        // Find all plannings created by this doctor
        $plannings = $this->planningRepository->findBy(['doctor' => $doctor]);
        
        $planningIds = array_map(function($planning) {
            return $planning->getId();
        }, $plannings);
        
        if (empty($planningIds)) {
            return [];
        }
        
        // Find all appointments for these plannings
        $appointments = $this->appointmentRepository->findByPlanningIds($planningIds);
        
        // Format appointments for calendar
        $events = [];
        foreach ($appointments as $appointment) {
            $startDateTime = $appointment->getStartAt();
            // Create an end time 1 hour after start time
            $endDateTime = clone $startDateTime;
            $endDateTime->modify('+1 hour');
            
            $patient = $appointment->getUser();
            $patientName = $patient ? $patient->getName() : 'Unknown Patient';
            
            $events[] = [
                'id' => $appointment->getId(),
                'title' => 'Appointment with ' . $patientName,
                'start' => $startDateTime->format('Y-m-d\TH:i:s'),
                'end' => $endDateTime->format('Y-m-d\TH:i:s'),
                'description' => 'Patient: ' . $patientName,
                'backgroundColor' => '#3788d8',
                'borderColor' => '#3788d8',
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'appointmentId' => $appointment->getId(),
                    'patientId' => $patient ? $patient->getId() : null,
                    'patientName' => $patientName,
                    'patientEmail' => $patient ? $patient->getEmail() : null
                ]
            ];
        }
        
        return $events;
    }
    
    /**
     * Get doctor availability from plannings
     * 
     * @param User $doctor
     * @return array
     */
    public function getDoctorAvailability(User $doctor): array
    {
        // Find all plannings created by this doctor
        $plannings = $this->planningRepository->findBy(['doctor' => $doctor]);
        
        $events = [];
        foreach ($plannings as $planning) {
            // Get the date range from planning
            $startDate = clone $planning->getStartDate();
            $endDate = clone $planning->getEndDate();
            $dailyStartTime = $planning->getDailyStartTime();
            $dailyEndTime = $planning->getDailyEndTime();
            
            // Loop through each day in the planning
            $currentDay = clone $startDate;
            while ($currentDay <= $endDate) {
                // Format the date for the current day
                $dateString = $currentDay->format('Y-m-d');
                
                // Create availability time slot
                $availabilityStart = new \DateTime($dateString . ' ' . $dailyStartTime->format('H:i:s'));
                $availabilityEnd = new \DateTime($dateString . ' ' . $dailyEndTime->format('H:i:s'));
                
                $events[] = [
                    'id' => 'planning-' . $planning->getId() . '-' . $dateString,
                    'title' => 'Available',
                    'start' => $availabilityStart->format('Y-m-d\TH:i:s'),
                    'end' => $availabilityEnd->format('Y-m-d\TH:i:s'),
                    'rendering' => 'background',
                    'backgroundColor' => '#c1e1c5',
                    'borderColor' => '#c1e1c5',
                    'textColor' => '#000000',
                    'allDay' => false,
                    'extendedProps' => [
                        'planningId' => $planning->getId()
                    ]
                ];
                
                // Go to the next day
                $currentDay->modify('+1 day');
            }
        }
        
        return $events;
    }
    
    /**
     * Sync appointments with Google Calendar (if needed)
     * 
     * @param Appointment $appointment
     * @return bool
     */
    public function syncAppointmentWithGoogleCalendar(Appointment $appointment): bool
    {
        try {
            $startDateTime = $appointment->getStartAt()->format('Y-m-d\TH:i:s');
            $endDateTime = (new \DateTime($appointment->getStartAt()->format('Y-m-d H:i:s')))->modify('+1 hour')->format('Y-m-d\TH:i:s');
            
            $patient = $appointment->getUser();
            $patientName = $patient ? $patient->getName() : 'Unknown Patient';
            $doctor = $appointment->getPlanning()->getDoctor();
            $doctorName = $doctor ? $doctor->getName() : 'Unknown Doctor';
            
            $summary = "Appointment: $doctorName with $patientName";
            $description = "Medical appointment between $doctorName and $patientName";
            
            // Use the CalendarService to add the event
            $result = $this->calendarService->addEvent(
                $summary,
                $description,
                $startDateTime,
                $endDateTime,
                'UTC'
            );
            
            return !isset($result['error']);
        } catch (\Exception $e) {
            return false;
        }
    }
     /**
     * Get all events for a doctor's calendar
     */
    public function getDoctorCalendarEvents(int $doctorId): array
    {
        $events = [];
        $this->logger->info('Fetching calendar events for doctor ID: ' . $doctorId);
        
        try {
            // Get doctor's planning schedules
            $plannings = $this->planningRepository->findBy(['doctor' => $doctorId]);
            $this->logger->info('Found ' . count($plannings) . ' planning entries');
            
            // Current date
            $currentDate = new \DateTime();
            
            // Add available hours as background events
            foreach ($plannings as $planning) {
                $startDate = $planning->getStartDate();
                $endDate = $planning->getEndDate();
                $dailyStartTime = $planning->getDailyStartTime();
                $dailyEndTime = $planning->getDailyEndTime();
                
                if (!$startDate || !$endDate || !$dailyStartTime || !$dailyEndTime) {
                    $this->logger->warning('Planning is missing required dates/times', [
                        'planningId' => $planning->getId()
                    ]);
                    continue;
                }
                
                // Calculate the number of days in this planning period
                $interval = $startDate->diff($endDate);
                $dayCount = $interval->days + 1; // Include both start and end date
                
                // For each day in the planning period
                for ($i = 0; $i < $dayCount; $i++) {
                    $date = clone $startDate;
                    $date->modify("+$i days");
                    
                    // Skip dates in the past
                    if ($date < $currentDate->modify('-1 day')) {
                        continue;
                    }
                    
                    // Create start and end times for this day
                    $startDateTime = clone $date;
                    $startDateTime->setTime(
                        (int) $dailyStartTime->format('H'),
                        (int) $dailyStartTime->format('i')
                    );
                    
                    $endDateTime = clone $date;
                    $endDateTime->setTime(
                        (int) $dailyEndTime->format('H'),
                        (int) $dailyEndTime->format('i')
                    );
                    
                    $events[] = [
                        'title' => 'Available',
                        'start' => $startDateTime->format('Y-m-d\TH:i:s'),
                        'end' => $endDateTime->format('Y-m-d\TH:i:s'),
                        'rendering' => 'background'
                    ];
                }
            }
            
            // Get doctor's appointments
            $appointments = $this->appointmentRepository->findByDoctor($doctorId);
            $this->logger->info('Found ' . count($appointments) . ' appointments');
            
            // Add appointments as regular events
            foreach ($appointments as $appointment) {
                $patient = $appointment->getUser();
                $startAt = $appointment->getStartAt();
                
                // Calculate end time (30 minutes by default)
                $endAt = clone $startAt;
                $endAt->modify('+30 minutes');
                
                $events[] = [
                    'id' => 'appointment-' . $appointment->getId(),
                    'title' => $patient ? $patient->getName() : 'Patient Appointment',
                    'start' => $startAt->format('Y-m-d\TH:i:s'),
                    'end' => $endAt->format('Y-m-d\TH:i:s'),
                    'extendedProps' => [
                        'appointmentId' => $appointment->getId(),
                        'patientName' => $patient ? $patient->getName() : 'Unknown',
                        'patientEmail' => $patient ? $patient->getEmail() : null,
                        'patientId' => $patient ? $patient->getId() : null,
                        'status' => 'confirmed' // Default status since your entity doesn't have this field
                    ]
                ];
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Error fetching calendar events: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            // Add error event
            $events[] = [
                'title' => 'Error loading calendar data',
                'start' => (new \DateTime())->format('Y-m-d\TH:i:s'),
                'backgroundColor' => '#ff0000',
                'borderColor' => '#ff0000',
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'description' => 'Error: ' . $e->getMessage()
                ]
            ];
        }
        
        return $events;
    }

    /**
     * Check if a specific time slot is available for a doctor
     */
    public function isTimeSlotAvailable(int $doctorId, \DateTime $start, \DateTime $end, ?int $excludeAppointmentId = null): bool
    {
        // Check if the time is within a planning period and within daily hours
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(p)')
           ->from('App:Planning', 'p')
           ->where('p.doctor = :doctorId')
           ->andWhere('p.startDate <= :date')
           ->andWhere('p.endDate >= :date')
           ->andWhere('p.dailyStartTime <= :time')
           ->andWhere('p.dailyEndTime >= :time')
           ->setParameter('doctorId', $doctorId)
           ->setParameter('date', $start->format('Y-m-d'))
           ->setParameter('time', $start->format('H:i:s'));
        
        $planningCount = $qb->getQuery()->getSingleScalarResult();
        
        if ($planningCount == 0) {
            return false; // No planning covers this time
        }
        
        // Check for conflicts with other appointments
        $conflictingAppointments = $this->appointmentRepository->findConflictingAppointments(
            $doctorId, 
            $start, 
            $end, 
            $excludeAppointmentId
        );
        
        return count($conflictingAppointments) === 0;
    }
}
