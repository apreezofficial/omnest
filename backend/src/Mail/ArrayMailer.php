<?php

declare(strict_types=1);

namespace Omnest\Mail;

/** Tests: keeps sent messages in memory. */
final class ArrayMailer implements Mailer
{
    /** @var list<Message> */
    public array $sent = [];

    public function send(Message $message): void
    {
        $this->sent[] = $message;
    }

    public function last(): ?Message
    {
        return $this->sent === [] ? null : $this->sent[array_key_last($this->sent)];
    }
}
