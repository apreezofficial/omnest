<?php

declare(strict_types=1);

namespace Omnest\Auth;

final class Passwords
{
    public static function algorithm(): string|int|null
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }

    public static function hash(string $password): string
    {
        return password_hash($password, self::algorithm());
    }

    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, self::algorithm());
    }

    /**
     * Burns the same time as a real check when the account doesn't exist,
     * so response timing doesn't reveal which emails are registered.
     */
    public static function dummyVerify(string $password): void
    {
        static $dummy = null;
        $dummy ??= self::hash('omnest-dummy-password');
        password_verify($password, $dummy);
    }
}
