<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Twig\Environment;
use Symfony\Component\Security\Core\Security;

class EmailService
{
    private MailerInterface $mailer;
    private Environment $twig;
    private string $senderEmail;
    private string $senderName;
    private Security $security;

    public function __construct(
        MailerInterface $mailer,
        Environment $twig,
        Security $security,
        string $senderEmail = 'noreply@clinic.com',
        string $senderName = 'Medical Clinic'
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->security = $security;
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
    }

    public function sendAppointmentRescheduledEmail(
        string $recipientEmail, 
        string $recipientName, 
        \DateTime $originalDateTime, 
        \DateTime $newDateTime, 
        string $doctorName,
        string $appointmentLink
    ): void {
        // Get current user's name
        $currentUser = $this->security->getUser();
        $currentUserName = $currentUser ? $currentUser->getUserIdentifier() : 'YoussefHarrabi';

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($recipientEmail, $recipientName))
            ->subject('Your Appointment Has Been Rescheduled')
            ->htmlTemplate('emails/appointment_rescheduled.html.twig')
            ->context([
                'recipientName' => $recipientName,
                'originalDate' => $originalDateTime->format('l, F j, Y'),
                'originalTime' => $originalDateTime->format('g:i A'),
                'newDate' => $newDateTime->format('l, F j, Y'),
                'newTime' => $newDateTime->format('g:i A'),
                'doctorName' => $doctorName,
                'appointmentLink' => $appointmentLink,
                'currentUser' => $currentUserName,
                'currentDate' => new \DateTime('2025-03-05 00:00:05')
            ]);

        $this->mailer->send($email);
    }
}