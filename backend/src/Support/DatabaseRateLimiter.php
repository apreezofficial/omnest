<?php

declare(strict_types=1);

namespace Omnest\Support;

use Omnest\Database\Database;

/** Shared across PHP-FPM workers via the `rate_limits` table. */
final class DatabaseRateLimiter implements RateLimiter
{
    public function __construct(private readonly Database $db)
    {
    }

    public function hit(string $key, int $maxHits, int $windowSeconds): array
    {
        $now = time();
        $windowStart = $now - ($now % $windowSeconds);
        $bucket = hash('sha256', $key . '|' . $windowStart);
        $expiresAt = date('Y-m-d H:i:s', $windowStart + $windowSeconds);

        $this->db->execute(
            'INSERT INTO rate_limits (bucket, hits, expires_at) VALUES (?, 1, ?)
             ON DUPLICATE KEY UPDATE hits = hits + 1',
            [$bucket, $expiresAt],
        );
        $hits = (int) $this->db->scalar('SELECT hits FROM rate_limits WHERE bucket = ?', [$bucket]);

        return [
            'allowed' => $hits <= $maxHits,
            'remaining' => max(0, $maxHits - $hits),
            'retry_after' => $windowStart + $windowSeconds - $now,
        ];
    }

    /** Called by the scheduler to drop old buckets. */
    public function purgeExpired(): int
    {
        return $this->db->execute('DELETE FROM rate_limits WHERE expires_at < NOW()');
    }
}
