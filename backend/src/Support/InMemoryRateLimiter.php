<?php

declare(strict_types=1);

namespace Omnest\Support;

/** For tests and single-process use. */
final class InMemoryRateLimiter implements RateLimiter
{
    /** @var array<string, int> */
    private array $hits = [];

    public function __construct(private readonly ?\Closure $clock = null)
    {
    }

    public function hit(string $key, int $maxHits, int $windowSeconds): array
    {
        $now = $this->clock ? ($this->clock)() : time();
        $windowStart = $now - ($now % $windowSeconds);
        $bucket = $key . '|' . $windowStart;
        $this->hits[$bucket] = ($this->hits[$bucket] ?? 0) + 1;

        return [
            'allowed' => $this->hits[$bucket] <= $maxHits,
            'remaining' => max(0, $maxHits - $this->hits[$bucket]),
            'retry_after' => $windowStart + $windowSeconds - $now,
        ];
    }
}
