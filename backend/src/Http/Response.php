<?php

declare(strict_types=1);

namespace Omnest\Http;

/**
 * Standard JSON envelope:
 *   success: { "data": ..., "meta": {...}? }
 *   error:   { "error": { "code": "...", "message": "...", "details": {...}? } }
 */
final class Response
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly int $status = 200,
        public readonly string $body = '',
        private array $headers = [],
    ) {
    }

    /** @param array<string, mixed>|null $meta */
    public static function success(mixed $data = null, int $status = 200, ?array $meta = null): self
    {
        $payload = ['data' => $data];
        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        return self::json($payload, $status);
    }

    public static function created(mixed $data = null): self
    {
        return self::success($data, 201);
    }

    public static function noContent(): self
    {
        return new self(204);
    }

    /** @param array<string, mixed>|null $details */
    public static function error(int $status, string $code, string $message, ?array $details = null): self
    {
        $error = ['code' => $code, 'message' => $message];
        if ($details !== null) {
            $error['details'] = $details;
        }

        return self::json(['error' => $error], $status);
    }

    /** @param array<string, mixed> $payload */
    public static function json(array $payload, int $status = 200): self
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return new self($status, $body, ['Content-Type' => 'application/json; charset=utf-8']);
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return $value;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    public function decoded(): array
    {
        return $this->body === '' ? [] : (array) json_decode($this->body, true, 512, JSON_THROW_ON_ERROR);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->body;
    }
}
