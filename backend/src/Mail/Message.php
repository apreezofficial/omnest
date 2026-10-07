<?php

declare(strict_types=1);

namespace Omnest\Mail;

final class Message
{
    public function __construct(
        public readonly string $to,
        public readonly string $subject,
        public readonly string $text,
        public readonly string $html,
    ) {
    }
}
