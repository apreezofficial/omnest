<?php

declare(strict_types=1);

namespace Omnest\Repositories;

use Omnest\Database\Database;
use Omnest\Support\Clock;

final class UsageRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /**
     * Stores one local day for one device. Values are absolute day totals; GREATEST keeps a
     * phone that lost its cache (or a late, older upload) from lowering numbers already reported.
     *
     * @param array<string, int> $apps package => foreground seconds
     */
    public function upsertDay(int $deviceId, string $date, array $apps): void
    {
        $now = Clock::db();

        foreach (array_chunk($apps, 200, true) as $chunk) {
            $rows = [];
            $bindings = [];
            foreach ($chunk as $package => $seconds) {
                $rows[] = '(?, ?, ?, ?, ?)';
                array_push($bindings, $deviceId, $date, $package, $seconds, $now);
            }
            $this->db->execute(
                'INSERT INTO usage_app_daily (device_id, usage_date, package, seconds, updated_at) VALUES '
                . implode(', ', $rows)
                . ' ON DUPLICATE KEY UPDATE seconds = GREATEST(seconds, VALUES(seconds)), updated_at = VALUES(updated_at)',
                $bindings,
            );
        }

        // Keep the day total in step with the per-app rows (capped at 24h).
        $this->db->execute(
            'INSERT INTO usage_daily (device_id, usage_date, total_seconds, updated_at)
             SELECT ?, ?, LEAST(COALESCE(SUM(seconds), 0), 86400), ? FROM usage_app_daily WHERE device_id = ? AND usage_date = ?
             ON DUPLICATE KEY UPDATE total_seconds = VALUES(total_seconds), updated_at = VALUES(updated_at)',
            [$deviceId, $date, $now, $deviceId, $date],
        );
    }

    public function markSynced(int $deviceId, string $timezone): void
    {
        $this->db->execute(
            'UPDATE devices SET usage_synced_at = ?, timezone = ? WHERE id = ?',
            [Clock::db(), $timezone, $deviceId],
        );
    }

    /**
     * Replaces the device's app list: listed apps are (re)activated, missing ones marked removed.
     *
     * @param list<array{package: string, label: string, system: bool}> $apps
     * @return array{added: int, removed: int}
     */
    public function syncApps(int $deviceId, array $apps): array
    {
        $now = Clock::db();
        $before = array_column(
            $this->db->select('SELECT package FROM installed_apps WHERE device_id = ? AND removed_at IS NULL', [$deviceId]),
            'package',
        );

        foreach (array_chunk($apps, 200) as $chunk) {
            $rows = [];
            $bindings = [];
            foreach ($chunk as $app) {
                $rows[] = '(?, ?, ?, ?, ?, NULL, ?)';
                array_push($bindings, $deviceId, $app['package'], $app['label'], (int) $app['system'], $now, $now);
            }
            $this->db->execute(
                'INSERT INTO installed_apps (device_id, package, label, is_system, first_seen_at, removed_at, updated_at) VALUES '
                . implode(', ', $rows)
                . ' ON DUPLICATE KEY UPDATE label = VALUES(label), is_system = VALUES(is_system), removed_at = NULL, updated_at = VALUES(updated_at)',
                $bindings,
            );
        }

        $packages = array_column($apps, 'package');
        $gone = array_values(array_diff($before, $packages));
        foreach (array_chunk($gone, 200) as $chunk) {
            $this->db->execute(
                'UPDATE installed_apps SET removed_at = ?, updated_at = ? WHERE device_id = ? AND package IN ('
                . implode(', ', array_fill(0, count($chunk), '?')) . ')',
                [$now, $now, $deviceId, ...$chunk],
            );
        }

        return ['added' => count(array_diff($packages, $before)), 'removed' => count($gone)];
    }

    /**
     * Daily totals for a child across all their phones (including unpaired ones: it's still their history).
     *
     * @return array<string, int> date => seconds
     */
    public function dailyTotals(int $childId, string $from, string $to): array
    {
        $rows = $this->db->select(
            'SELECT ud.usage_date AS d, SUM(ud.total_seconds) AS s
             FROM usage_daily ud JOIN devices dv ON dv.id = ud.device_id
             WHERE dv.child_id = ? AND ud.usage_date BETWEEN ? AND ?
             GROUP BY ud.usage_date',
            [$childId, $from, $to],
        );

        $totals = [];
        foreach ($rows as $row) {
            $totals[$row['d']] = min(86400, (int) $row['s']);
        }

        return $totals;
    }

    /** @return list<array{package: string, label: string, seconds: int}> */
    public function topApps(int $childId, string $from, string $to, int $limit): array
    {
        $rows = $this->db->select(
            'SELECT a.package, SUM(a.seconds) AS seconds, MAX(ia.label) AS label
             FROM usage_app_daily a
             JOIN devices dv ON dv.id = a.device_id
             LEFT JOIN installed_apps ia ON ia.device_id = a.device_id AND ia.package = a.package
             WHERE dv.child_id = ? AND a.usage_date BETWEEN ? AND ?
             GROUP BY a.package
             HAVING SUM(a.seconds) > 0
             ORDER BY seconds DESC, a.package
             LIMIT ' . max(1, min(100, $limit)),
            [$childId, $from, $to],
        );

        return array_map(static fn (array $r) => [
            'package' => $r['package'],
            'label' => $r['label'] ?? $r['package'],
            'seconds' => (int) $r['seconds'],
        ], $rows);
    }

    /** Timezone of the child's most recently active phone, or null. */
    public function childTimezone(int $childId): ?string
    {
        $tz = $this->db->scalar(
            'SELECT timezone FROM devices WHERE child_id = ? AND timezone IS NOT NULL
             ORDER BY usage_synced_at DESC, last_seen_at DESC LIMIT 1',
            [$childId],
        );

        return $tz === null ? null : (string) $tz;
    }

    public function lastSyncedAt(int $childId): ?string
    {
        $at = $this->db->scalar('SELECT MAX(usage_synced_at) FROM devices WHERE child_id = ?', [$childId]);

        return $at === null ? null : (string) $at;
    }

    /** Retention: delete usage older than $date. Returns rows deleted. */
    public function purgeBefore(string $date): int
    {
        return $this->db->execute('DELETE FROM usage_app_daily WHERE usage_date < ?', [$date])
            + $this->db->execute('DELETE FROM usage_daily WHERE usage_date < ?', [$date]);
    }
}
