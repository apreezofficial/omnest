<?php

declare(strict_types=1);

namespace Omnest\Mail;

use Psr\Log\LoggerInterface;

/** Local development: writes emails to the app log instead of sending them. */
final class LogMailer implements Mailer
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function send(Message $message): void
    {
        $this->logger->info('Email (not sent, MAIL_DRIVER=log)', [
            'to' => $message->to,
            'subject' => $message->subject,
            'text' => $message->text,
        ]);
    }
}
