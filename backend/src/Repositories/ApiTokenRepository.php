<?php

declare(strict_types=1);

namespace Omnest\Repositories;

use Omnest\Auth\Tokens;
use Omnest\Database\Database;
use Omnest\Support\Clock;

/** Parent login tokens. */
final class ApiTokenRepository
{
    public const LIFETIME = '+30 days';

    public function __construct(private readonly Database $db)
    {
    }

    /** Issues a token and returns the plain value (shown once). */
    public function issue(int $userId, string $name): string
    {
        $token = Tokens::generate(Tokens::PARENT);
        $this->db->insert('api_tokens', [
            'user_id' => $userId,
            'token_hash' => $token['hash'],
            'name' => $name,
            'expires_at' => Clock::db(self::LIFETIME),
            'created_at' => Clock::db(),
        ]);

        return $token['plain'];
    }

    /**
     * Active token + its user, or null.
     *
     * @return array<string, mixed>|null
     */
    public function findActive(string $plain): ?array
    {
        return $this->db->first(
            'SELECT t.id AS token_id, t.last_used_at, u.*
             FROM api_tokens t JOIN users u ON u.id = t.user_id
             WHERE t.token_hash = ? AND t.revoked_at IS NULL AND t.expires_at > ?',
            [Tokens::hash($plain), Clock::db()],
        );
    }

    /** Sliding expiry: extend at most once an hour to keep writes low. */
    public function touch(int $tokenId, ?string $lastUsedAt): void
    {
        if ($lastUsedAt !== null && $lastUsedAt > Clock::db('-1 hour')) {
            return;
        }
        $this->db->execute(
            'UPDATE api_tokens SET last_used_at = ?, expires_at = ? WHERE id = ?',
            [Clock::db(), Clock::db(self::LIFETIME), $tokenId],
        );
    }

    public function revoke(int $tokenId): void
    {
        $this->db->execute('UPDATE api_tokens SET revoked_at = ? WHERE id = ? AND revoked_at IS NULL', [Clock::db(), $tokenId]);
    }

    public function revokeAllForUser(int $userId): void
    {
        $this->db->execute('UPDATE api_tokens SET revoked_at = ? WHERE user_id = ? AND revoked_at IS NULL', [Clock::db(), $userId]);
    }
}
