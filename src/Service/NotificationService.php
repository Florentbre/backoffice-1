<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public function notify(string $to, string $subject, string $message): void
    {
        $mail = (new Email())
            ->from('backoffice@internal.local')
            ->to($to)
            ->subject($subject)
            ->text($message);

        $this->mailer->send($mail);
    }
}
