<?php

declare(strict_types=1);

namespace Omnest\Support;

interface RateLimiter
{
    /**
     * Records one hit for $key in the current window.
     *
     * @return array{allowed: bool, remaining: int, retry_after: int}
     */
    public function hit(string $key, int $maxHits, int $windowSeconds): array;
}
