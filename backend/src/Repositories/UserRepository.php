<?php

declare(strict_types=1);

namespace Omnest\Repositories;

use Omnest\Database\Database;
use Omnest\Support\Clock;

final class UserRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->db->first('SELECT * FROM users WHERE id = ?', [$id]);
    }

    /** @return array<string, mixed>|null */
    public function findByEmail(string $email): ?array
    {
        return $this->db->first('SELECT * FROM users WHERE email = ?', [self::normalizeEmail($email)]);
    }

    public function create(string $name, string $email, string $passwordHash): int
    {
        $now = Clock::db();

        return $this->db->insert('users', [
            'name' => $name,
            'email' => self::normalizeEmail($email),
            'password_hash' => $passwordHash,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $this->db->execute(
            'UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?',
            [$passwordHash, Clock::db(), $id],
        );
    }

    public function markEmailVerified(int $id): void
    {
        $this->db->execute(
            'UPDATE users SET email_verified_at = COALESCE(email_verified_at, ?), updated_at = ? WHERE id = ?',
            [Clock::db(), Clock::db(), $id],
        );
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /**
     * Public shape of a user for API responses.
     *
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public static function present(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'email_verified' => $user['email_verified_at'] !== null,
            'created_at' => Clock::iso($user['created_at']),
        ];
    }
}
