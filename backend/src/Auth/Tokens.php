<?php

declare(strict_types=1);

namespace Omnest\Auth;

/**
 * Opaque bearer tokens: a readable prefix + 32 random bytes.
 * The prefix makes leaked tokens easy to recognise ("omp_" parent, "omd_" device, "omx_" one-time link).
 * Only hash() of a token is ever stored.
 */
final class Tokens
{
    public const PARENT = 'omp_';
    public const DEVICE = 'omd_';
    public const LINK = 'omx_';

    /** @return array{plain: string, hash: string} */
    public static function generate(string $prefix): array
    {
        $plain = $prefix . bin2hex(random_bytes(32));

        return ['plain' => $plain, 'hash' => self::hash($plain)];
    }

    public static function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }
}
