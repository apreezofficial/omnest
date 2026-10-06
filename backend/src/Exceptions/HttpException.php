<?php

declare(strict_types=1);

namespace Omnest\Exceptions;

use RuntimeException;

class HttpException extends RuntimeException
{
    /** @param array<string, mixed>|null $details */
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly ?array $details = null,
    ) {
        parent::__construct($message);
    }

    public static function notFound(string $message = 'Not found.'): self
    {
        return new self(404, 'not_found', $message);
    }

    public static function methodNotAllowed(): self
    {
        return new self(405, 'method_not_allowed', 'Method not allowed.');
    }

    public static function unauthorized(string $message = 'Authentication required.'): self
    {
        return new self(401, 'unauthorized', $message);
    }

    public static function forbidden(string $message = 'You do not have access to this.'): self
    {
        return new self(403, 'forbidden', $message);
    }

    public static function badRequest(string $message): self
    {
        return new self(400, 'bad_request', $message);
    }

    public static function tooManyRequests(int $retryAfter): self
    {
        return new self(429, 'too_many_requests', 'Too many requests. Try again shortly.', ['retry_after' => $retryAfter]);
    }
}
