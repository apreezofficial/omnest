<?php

declare(strict_types=1);

namespace Omnest\Mail;

use RuntimeException;

/** Sends through the Resend HTTP API (https://resend.com/docs/api-reference/emails/send-email). */
final class ResendMailer implements Mailer
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $from,
    ) {
    }

    public function send(Message $message): void
    {
        $payload = json_encode([
            'from' => $this->from,
            'to' => [$message->to],
            'subject' => $message->subject,
            'text' => $message->text,
            'html' => $message->html,
        ], JSON_THROW_ON_ERROR);

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $status >= 300) {
            throw new RuntimeException("Resend failed ($status): " . ($error ?: (string) $body));
        }
    }
}
