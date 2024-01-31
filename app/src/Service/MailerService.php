<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class MailerService
{
    public function __construct(private readonly MailerInterface $mailer, private readonly string $sender_email)
    {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function send($to, $email, $subject, $template, $context): void
    {
        $email = (new TemplatedEmail())
            ->from($this->sender_email)
            ->to($email)
            //->cc('cc@example.com')
            //->bcc('bcc@example.com')
            //->replyTo('fabien@example.com')
            //->priority(Email::PRIORITY_HIGH)
            ->subject($subject)
            ->htmlTemplate('emails/' . $template . '.html.twig')

            // pass variables (name => value) to the template
            ->context($context);

        $this->mailer->send($email);
    }
}
