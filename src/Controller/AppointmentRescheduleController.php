<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Service\AppointmentService;
use App\Service\EmailService;
use App\Service\AppointmentMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

class AppointmentRescheduleController extends AbstractController


{
     private EntityManagerInterface $entityManager;
    private AppointmentService $appointmentService;
    private AppointmentMailer $appointmentMailer;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        AppointmentService $appointmentService,
        AppointmentMailer $appointmentMailer,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->appointmentService = $appointmentService;
        $this->appointmentMailer = $appointmentMailer;
        $this->logger = $logger;
    }


    #[Route('/appointment/reschedule', name: 'appointment_reschedule', methods: ['POST'])]
    #[IsGranted('ROLE_DOCTOR')]
    public function reschedule(Request $request): JsonResponse
    {
        // Log request data for debugging
        $content = $request->getContent();
        $this->logger->info('Appointment reschedule request received', [
            'content' => $content,
            'time' => date('Y-m-d H:i:s')
        ]);
        
        try {
            // Parse request data
            $data = json_decode($content, true);
            
            if (!$data || !isset($data['appointmentId']) || !isset($data['date']) || !isset($data['time'])) {
                return $this->json(['success' => false, 'message' => 'Missing required data'], 400);
            }
            
            $appointmentId = $data['appointmentId'];
            $date = $data['date'];
            $time = $data['time'];
            $notifyPatient = $data['notifyPatient'] ?? false;
            
            // Load the appointment
            $appointment = $this->entityManager->getRepository(Appointment::class)->find($appointmentId);
            
            if (!$appointment) {
                return $this->json(['success' => false, 'message' => 'Appointment not found'], 404);
            }
            
            // Store original time for email notification
            $originalDateTime = clone $appointment->getStartAt();
            
            // Create the new date time
            try {
                $dateTime = new \DateTime("$date $time");
            } catch (\Exception $e) {
                return $this->json(['success' => false, 'message' => 'Invalid date or time format'], 400);
            }
            
            // Update the appointment time
            $appointment->setStartAt($dateTime);
            
            // Save the changes
            $this->entityManager->flush();
            
            $emailSent = false;
            $emailError = null;
            
            // Send email notification if requested
            if ($notifyPatient && 
                $appointment->getUser() && 
                $appointment->getUser()->getEmail()) {
                try {
                    $this->appointmentMailer->sendAppointmentRescheduledEmail(
                        $appointment->getUser()->getEmail(),
                        $appointment->getUser()->getName(),
                        $originalDateTime,
                        $dateTime,
                        $appointment->getPlanning()->getDoctor()->getName(),
                        $this->generateUrl('appointment_show', ['id' => $appointment->getId()], true)
                    );
                    $emailSent = true;
                } catch (\Exception $e) {
                    $this->logger->error('Email notification failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $emailError = $e->getMessage();
                    // Continue execution, we'll handle this in the response
                }
            }
            
            $responseMessage = 'Appointment successfully rescheduled';
            if ($notifyPatient) {
                $responseMessage .= $emailSent ? ' and patient notified' : 
                    ' but failed to send notification: ' . $emailError;
            }
            
            return $this->json([
                'success' => true,
                'message' => $responseMessage,
                'appointmentId' => $appointment->getId(),
                'newDateTime' => $dateTime->format('Y-m-d H:i:s'),
                'emailSent' => $emailSent,
                'emailError' => $emailError
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error rescheduling appointment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'time' => date('Y-m-d H:i:s')
            ]);
            
            return $this->json([
                'success' => false, 
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}