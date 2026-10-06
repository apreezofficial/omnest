<?php

declare(strict_types=1);

namespace Omnest\Http\Middleware;

use Closure;
use Omnest\Exceptions\HttpException;
use Omnest\Http\Request;
use Omnest\Http\Response;
use Omnest\Support\RateLimiter;

/** Fixed-window limiter. Keyed by client IP unless a key function is given. */
final class RateLimit implements Middleware
{
    /** @param (Closure(Request): string)|null $keyFn */
    public function __construct(
        private readonly RateLimiter $limiter,
        private readonly string $name,
        private readonly int $maxHits,
        private readonly int $windowSeconds,
        private readonly ?Closure $keyFn = null,
    ) {
    }

    public function process(Request $request, callable $next): Response
    {
        $key = $this->name . ':' . ($this->keyFn ? ($this->keyFn)($request) : $request->ip);
        $result = $this->limiter->hit($key, $this->maxHits, $this->windowSeconds);

        if (!$result['allowed']) {
            throw HttpException::tooManyRequests($result['retry_after']);
        }

        return $next($request)
            ->withHeader('X-RateLimit-Limit', (string) $this->maxHits)
            ->withHeader('X-RateLimit-Remaining', (string) $result['remaining']);
    }
}
