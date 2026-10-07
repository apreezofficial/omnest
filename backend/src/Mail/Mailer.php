<?php

declare(strict_types=1);

namespace Omnest\Mail;

interface Mailer
{
    public function send(Message $message): void;
}
