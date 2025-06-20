<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

class GmailMailer
{
    private MailerInterface $mailer;
    private string $defaultSender;
    private string $defaultSenderName;
    private ?string $lastError = null;

    public function __construct(
        MailerInterface $mailer, 
        string $defaultSender = 'az.backup04@gmail.com', 
        string $defaultSenderName = 'clinique'
    ) {
        $this->mailer = $mailer;
        $this->defaultSender = $defaultSender;
        $this->defaultSenderName = $defaultSenderName;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }
    
    public function sendEmail(
        string $to,
        string $subject,
        string $htmlContent,
        ?string $textContent = null,
        ?string $toName = null,
        ?string $attachmentPath = null
    ): bool {
        try {
            $email = (new Email())
                ->from(sprintf('%s <%s>', $this->defaultSenderName, $this->defaultSender))
                ->to($toName ? sprintf('%s <%s>', $toName, $to) : $to)
                ->subject($subject)
                ->html($htmlContent);
            
            if ($textContent) {
                $email->text($textContent);
            }

            if ($attachmentPath && file_exists($attachmentPath)) {
                $email->attachFromPath($attachmentPath);
            }
            
            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    
    public function getDebugInfo(): array
    {
        return [
            'sender_email' => $this->defaultSender,
            'sender_name' => $this->defaultSenderName,
            'mailer_class' => get_class($this->mailer),
            'last_error' => $this->lastError,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ];
    }
}