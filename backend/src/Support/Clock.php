<?php

declare(strict_types=1);

namespace Omnest\Support;

use DateTimeImmutable;
use DateTimeZone;

/** UTC clock. Tests can freeze it. All DB datetimes are UTC ("Y-m-d H:i:s"). */
final class Clock
{
    private static ?DateTimeImmutable $frozen = null;

    public static function now(): DateTimeImmutable
    {
        return self::$frozen ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    /** Current time (optionally shifted, e.g. "+30 days") formatted for the database. */
    public static function db(string $modify = ''): string
    {
        $time = $modify === '' ? self::now() : self::now()->modify($modify);

        return $time->format('Y-m-d H:i:s');
    }

    /** Database datetime -> ISO 8601 for API responses. */
    public static function iso(?string $dbValue): ?string
    {
        if ($dbValue === null) {
            return null;
        }

        return (new DateTimeImmutable($dbValue, new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z');
    }

    public static function freeze(string $at): void
    {
        self::$frozen = new DateTimeImmutable($at, new DateTimeZone('UTC'));
    }

    public static function unfreeze(): void
    {
        self::$frozen = null;
    }
}
