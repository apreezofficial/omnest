<?php

declare(strict_types=1);

namespace Omnest\Services;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Omnest\Database\Database;
use Omnest\Exceptions\ValidationException;
use Omnest\Repositories\UsageRepository;
use Omnest\Support\Clock;
use Omnest\Support\Config;

/**
 * Phone -> API: per-app foreground seconds per local day (absolute totals, idempotent).
 * API -> parent: day and range reports in the child's own timezone.
 */
final class UsageService
{
    public const MAX_DAYS_PER_UPLOAD = 8;
    public const MAX_APPS_PER_DAY = 500;
    public const MAX_APPS_LIST = 1000;
    public const RETENTION_DAYS = 90;
    public const RANGE_OPTIONS = [7, 14, 30];

    private const PACKAGE = '/^[A-Za-z][A-Za-z0-9_]*(\.[A-Za-z0-9_]+)+$/';

    public function __construct(
        private readonly Database $db,
        private readonly UsageRepository $usage,
        private readonly Config $config,
    ) {
    }

    /**
     * @param array<string, mixed> $device authenticated device row
     * @param array<string, mixed> $payload {timezone, days: [{date, apps: [{package, seconds}]}]}
     * @return array{accepted: list<string>}
     */
    public function ingest(array $device, array $payload): array
    {
        $tz = self::timezone($payload['timezone'] ?? null);
        $days = $payload['days'] ?? null;
        if (!is_array($days) || !array_is_list($days) || $days === [] || count($days) > self::MAX_DAYS_PER_UPLOAD) {
            throw new ValidationException(['days' => ['Send 1 to ' . self::MAX_DAYS_PER_UPLOAD . ' days.']]);
        }

        $today = Clock::now()->setTimezone($tz)->format('Y-m-d');
        $oldest = Clock::now()->setTimezone($tz)->modify('-' . (self::RETENTION_DAYS - 1) . ' days')->format('Y-m-d');

        $clean = [];
        $errors = [];
        foreach ($days as $i => $day) {
            $date = is_array($day) ? ($day['date'] ?? null) : null;
            if (!self::isDate($date) || $date > $today || $date < $oldest) {
                $errors["days.$i.date"] = ['Use a recent date (YYYY-MM-DD), not in the future.'];
                continue;
            }
            $apps = $day['apps'] ?? null;
            if (!is_array($apps) || !array_is_list($apps) || count($apps) > self::MAX_APPS_PER_DAY) {
                $errors["days.$i.apps"] = ['Send a list of up to ' . self::MAX_APPS_PER_DAY . ' apps.'];
                continue;
            }

            $perApp = [];
            foreach ($apps as $j => $app) {
                $package = is_array($app) ? ($app['package'] ?? null) : null;
                $seconds = is_array($app) ? ($app['seconds'] ?? null) : null;
                if (!is_string($package) || strlen($package) > 191 || preg_match(self::PACKAGE, $package) !== 1) {
                    $errors["days.$i.apps.$j.package"] = ['Not a valid package name.'];
                    continue;
                }
                if (!is_int($seconds) || $seconds < 0 || $seconds > 86400) {
                    $errors["days.$i.apps.$j.seconds"] = ['Must be 0 to 86400.'];
                    continue;
                }
                $perApp[$package] = max($perApp[$package] ?? 0, $seconds);
            }
            $clean[$date] = $perApp;
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $deviceId = (int) $device['id'];
        $this->db->transaction(function () use ($deviceId, $clean, $tz): void {
            foreach ($clean as $date => $apps) {
                if ($apps !== []) {
                    $this->usage->upsertDay($deviceId, $date, $apps);
                }
            }
            $this->usage->markSynced($deviceId, $tz->getName());
        });

        return ['accepted' => array_keys($clean)];
    }

    /**
     * @param array<string, mixed> $device
     * @param array<string, mixed> $payload {apps: [{package, label, system?}]}
     * @return array{added: int, removed: int}
     */
    public function syncApps(array $device, array $payload): array
    {
        $apps = $payload['apps'] ?? null;
        if (!is_array($apps) || !array_is_list($apps) || count($apps) > self::MAX_APPS_LIST) {
            throw new ValidationException(['apps' => ['Send a list of up to ' . self::MAX_APPS_LIST . ' apps.']]);
        }

        $clean = [];
        $errors = [];
        foreach ($apps as $i => $app) {
            $package = is_array($app) ? ($app['package'] ?? null) : null;
            $label = is_array($app) && is_string($app['label'] ?? null) ? trim($app['label']) : '';
            if (!is_string($package) || strlen($package) > 191 || preg_match(self::PACKAGE, $package) !== 1) {
                $errors["apps.$i.package"] = ['Not a valid package name.'];
                continue;
            }
            $clean[$package] = [
                'package' => $package,
                'label' => mb_substr($label !== '' ? $label : $package, 0, 120),
                'system' => (bool) ($app['system'] ?? false),
            ];
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $this->db->transaction(fn () => $this->usage->syncApps((int) $device['id'], array_values($clean)));
    }

    /** @return array<string, mixed> */
    public function dayReport(int $childId, ?string $date): array
    {
        $tz = $this->childTimezone($childId);
        $today = Clock::now()->setTimezone($tz)->format('Y-m-d');
        if ($date !== null && (!self::isDate($date) || $date > $today)) {
            throw new ValidationException(['date' => ['Use a date (YYYY-MM-DD) up to today.']]);
        }
        $date ??= $today;

        return [
            'date' => $date,
            'is_today' => $date === $today,
            'timezone' => $tz->getName(),
            'total_seconds' => $this->usage->dailyTotals($childId, $date, $date)[$date] ?? 0,
            'apps' => $this->usage->topApps($childId, $date, $date, 20),
            'last_synced_at' => Clock::iso($this->usage->lastSyncedAt($childId)),
        ];
    }

    /** @return array<string, mixed> */
    public function rangeReport(int $childId, int $days): array
    {
        if (!in_array($days, self::RANGE_OPTIONS, true)) {
            throw new ValidationException(['days' => ['Pick 7, 14 or 30 days.']]);
        }

        $tz = $this->childTimezone($childId);
        $end = Clock::now()->setTimezone($tz);
        $to = $end->format('Y-m-d');
        $from = $end->modify('-' . ($days - 1) . ' days')->format('Y-m-d');

        $totals = $this->usage->dailyTotals($childId, $from, $to);
        $series = [];
        for ($d = new DateTimeImmutable($from); $d->format('Y-m-d') <= $to; $d = $d->modify('+1 day')) {
            $key = $d->format('Y-m-d');
            $series[] = ['date' => $key, 'total_seconds' => $totals[$key] ?? 0];
        }

        $sum = array_sum(array_column($series, 'total_seconds'));
        // Average over days that have data, so a phone paired yesterday doesn't look "barely used".
        $withData = count(array_filter($series, static fn (array $s) => $s['total_seconds'] > 0));

        return [
            'from' => $from,
            'to' => $to,
            'timezone' => $tz->getName(),
            'days' => $series,
            'total_seconds' => $sum,
            'average_seconds' => $withData > 0 ? intdiv($sum, $withData) : 0,
            'top_apps' => $this->usage->topApps($childId, $from, $to, 5),
            'last_synced_at' => Clock::iso($this->usage->lastSyncedAt($childId)),
        ];
    }

    private function childTimezone(int $childId): DateTimeZone
    {
        $name = $this->usage->childTimezone($childId) ?? $this->config->string('default_timezone', 'Africa/Lagos');

        return new DateTimeZone($name);
    }

    private static function timezone(mixed $name): DateTimeZone
    {
        if (!is_string($name) || $name === '' || strlen($name) > 64) {
            throw new ValidationException(['timezone' => ['Send the phone\'s timezone, e.g. Africa/Lagos.']]);
        }
        try {
            return new DateTimeZone($name);
        } catch (Exception) {
            throw new ValidationException(['timezone' => ['Unknown timezone.']]);
        }
    }

    private static function isDate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $d !== false && $d->format('Y-m-d') === $value;
    }
}
