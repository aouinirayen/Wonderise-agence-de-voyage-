<?php
namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailService
{
    private MailerInterface $mailer;
    private Environment $twig;
    private ?LoggerInterface $logger;
    private bool $sendEmails = true; // Activé

    public function __construct(MailerInterface $mailer, Environment $twig, LoggerInterface $logger = null)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->logger = $logger;
    }

    public function sendAdminNotification(string $adminEmail, array $data): void
    {
        if (!$this->sendEmails) {
            $this->logEmail($adminEmail, 'Nouvelle Réclamation Reçue', $data);
            return;
        }

        $htmlContent = $this->twig->render('emails/reclamation_admin.html.twig', $data);

        $email = (new Email())
            ->from('charfifatmaezzahra@gmail.com')
            ->to($adminEmail)
            ->subject('Nouvelle Réclamation Reçue')
            ->html($htmlContent);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            // If there's an error with the mailer, log it and disable future email sending
            if ($this->logger) {
                $this->logger->error('Email sending failed: ' . $e->getMessage());
            }
            $this->sendEmails = false;
            
            // Log the email that was attempted to be sent
            $this->logEmail($adminEmail, 'Nouvelle Réclamation Reçue', $data);
        }
    }
    
    private function logEmail(string $recipient, string $subject, array $data): void
    {
        if ($this->logger) {
            $this->logger->info(sprintf(
                'Email would have been sent to: %s, Subject: %s, Data: %s',
                $recipient,
                $subject,
                json_encode($data)
            ));
        }
    }
}
