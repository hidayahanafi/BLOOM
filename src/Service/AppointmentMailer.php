<?php

namespace App\Service;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\SecurityBundle\Security;

class AppointmentMailer
{
    private Mailer $mailer;
    private string $senderEmail;
    private string $senderName;
    private Security $security;
    private ?string $lastError = null;

    public function __construct(
        Security $security,
        string $dsn = null,
        string $senderEmail = null, 
        string $senderName = 'Medical Clinic Appointments'
    ) {
        $this->security = $security;
        
        // Use provided DSN or fallback to env variable
        $dsn = $dsn ?? $_ENV['APPOINTMENT_MAILER_DSN'] ?? $_ENV['MAILER_DSN'] ?? null;
        if (!$dsn) {
            throw new \InvalidArgumentException('Appointment mailer DSN is not configured');
        }
        
        $transport = Transport::fromDsn($dsn);
        $this->mailer = new Mailer($transport);
        
        $this->senderEmail = $senderEmail ?? $_ENV['APPOINTMENT_MAILER_FROM'] ?? $_ENV['MAILER_FROM'] ?? null;
        if (!$this->senderEmail) {
            throw new \InvalidArgumentException('Appointment mailer sender email is not configured');
        }
        
        $this->senderName = $senderName ?? $_ENV['APPOINTMENT_MAILER_NAME'] ?? 'Medical Clinic Appointments';
    }
    
    /**
     * Get last error message if any
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Send appointment rescheduled email notification
     */
    public function sendAppointmentRescheduledEmail(
        string $recipientEmail, 
        string $recipientName, 
        \DateTime $originalDateTime, 
        \DateTime $newDateTime, 
        string $doctorName,
        string $appointmentLink
    ): bool {
        try {
            // Get current user's name
            $currentUser = $this->security->getUser();
            $currentUserName = $currentUser ? $currentUser->getUserIdentifier() : 'YoussefHarrabi';
            
            // Create a well-designed HTML email
            $htmlContent = $this->getStyledHtmlEmail(
                $recipientName,
                $doctorName,
                $currentUserName,
                $originalDateTime,
                $newDateTime,
                $appointmentLink
            );
            
            // Create plain text version
            $textContent = "Dear {$recipientName},\n\n" .
                "Your appointment with Dr. {$doctorName} has been rescheduled by {$currentUserName}.\n\n" .
                "Original Date/Time: {$originalDateTime->format('l, F j, Y')} at {$originalDateTime->format('g:i A')}\n" .
                "New Date/Time: {$newDateTime->format('l, F j, Y')} at {$newDateTime->format('g:i A')}\n\n" .
                "To view your appointment details, please visit: {$appointmentLink}\n\n" .
                "If you have any questions or need to make further changes, please contact our office.\n\n" .
                "Best regards,\n" .
                "Medical Clinic Team";
            
            // Create email
            $email = new Email();
            $email->from(new Address($this->senderEmail, $this->senderName))
                ->to(new Address($recipientEmail, $recipientName))
                ->subject('Your Appointment Has Been Rescheduled')
                ->text($textContent)
                ->html($htmlContent);

            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    
    /**
     * Generate well-designed HTML email content
     */
    private function getStyledHtmlEmail(
        string $recipientName,
        string $doctorName,
        string $currentUserName,
        \DateTime $originalDateTime,
        \DateTime $newDateTime,
        string $appointmentLink
    ): string {
        $currentYear = date('Y', strtotime('2025-03-05 12:43:11'));
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Rescheduled</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        .header {
            background-color: #4a89dc;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            border: 1px solid #eee;
            padding: 20px;
        }
        .footer {
            font-size: 12px;
            text-align: center;
            padding: 20px;
            color: #666;
            background-color: #f1f1f1;
            border-radius: 0 0 5px 5px;
        }
        .appointment-details {
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #4a89dc;
        }
        .old {
            text-decoration: line-through;
            color: #888;
        }
        .new {
            font-weight: bold;
            color: #4a89dc;
        }
        .button {
            display: inline-block;
            background-color: #4a89dc;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin-top: 15px;
            font-weight: bold;
        }
        .logo {
            max-width: 150px;
            margin: 0 auto 10px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Appointment Rescheduled</h1>
        </div>
        <div class="content">
            <p>Dear {$recipientName},</p>
            
            <p>Your appointment with Dr. {$doctorName} has been rescheduled by {$currentUserName}.</p>
            
            <div class="appointment-details">
                <p><span class="old">Original Date: {$originalDateTime->format('l, F j, Y')}</span><br>
                <span class="new">New Date: {$newDateTime->format('l, F j, Y')}</span></p>
                
                <p><span class="old">Original Time: {$originalDateTime->format('g:i A')}</span><br>
                <span class="new">New Time: {$newDateTime->format('g:i A')}</span></p>
            </div>
            
            <p>If you have any questions or need to make further changes, please contact our office.</p>
            
        
        </div>
        <div class="footer">
            <p>This is an automated message from Medical Clinic.</p>
            <p>&copy; {$currentYear} Medical Clinic. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}