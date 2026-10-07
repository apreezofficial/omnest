<?php

declare(strict_types=1);

namespace Omnest\Services;

use Omnest\Database\Database;
use Omnest\Repositories\UsageRepository;
use Omnest\Support\Clock;

/** Retention and housekeeping. Run daily from cron: php bin/cleanup.php */
final class Maintenance
{
    public function __construct(
        private readonly Database $db,
        private readonly UsageRepository $usage,
    ) {
    }

    /** @return array<string, int> rows deleted per kind */
    public function run(): array
    {
        $cutoffDate = Clock::now()->modify('-' . UsageService::RETENTION_DAYS . ' days')->format('Y-m-d');

        return [
            'usage' => $this->usage->purgeBefore($cutoffDate),
            'rate_limits' => $this->db->execute('DELETE FROM rate_limits WHERE expires_at < ?', [Clock::db()]),
            'pairing_codes' => $this->db->execute('DELETE FROM pairing_codes WHERE expires_at < ?', [Clock::db('-1 day')]),
            'user_tokens' => $this->db->execute(
                'DELETE FROM user_tokens WHERE expires_at < ? OR used_at < ?',
                [Clock::db('-7 days'), Clock::db('-7 days')],
            ),
            'api_tokens' => $this->db->execute(
                'DELETE FROM api_tokens WHERE expires_at < ? OR revoked_at < ?',
                [Clock::db('-30 days'), Clock::db('-30 days')],
            ),
        ];
    }
}
