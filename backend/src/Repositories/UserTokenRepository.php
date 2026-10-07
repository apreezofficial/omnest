<?php

declare(strict_types=1);

namespace Omnest\Repositories;

use Omnest\Auth\Tokens;
use Omnest\Database\Database;
use Omnest\Support\Clock;

/** One-time tokens sent by email (verify email, reset password). */
final class UserTokenRepository
{
    public const VERIFY_EMAIL = 'verify_email';
    public const RESET_PASSWORD = 'reset_password';

    public function __construct(private readonly Database $db)
    {
    }

    /** Invalidates earlier tokens of the same type and returns a fresh plain token. */
    public function issue(int $userId, string $type, string $lifetime): string
    {
        $this->db->execute(
            'UPDATE user_tokens SET used_at = ? WHERE user_id = ? AND type = ? AND used_at IS NULL',
            [Clock::db(), $userId, $type],
        );

        $token = Tokens::generate(Tokens::LINK);
        $this->db->insert('user_tokens', [
            'user_id' => $userId,
            'type' => $type,
            'token_hash' => $token['hash'],
            'expires_at' => Clock::db($lifetime),
            'created_at' => Clock::db(),
        ]);

        return $token['plain'];
    }

    /**
     * Marks a valid token used and returns its user id. Null if unknown, expired or already used.
     * The conditional UPDATE makes this safe against double use.
     */
    public function consume(string $plain, string $type): ?int
    {
        $row = $this->db->first(
            'SELECT id, user_id FROM user_tokens
             WHERE token_hash = ? AND type = ? AND used_at IS NULL AND expires_at > ?',
            [Tokens::hash($plain), $type, Clock::db()],
        );
        if ($row === null) {
            return null;
        }

        $claimed = $this->db->execute(
            'UPDATE user_tokens SET used_at = ? WHERE id = ? AND used_at IS NULL',
            [Clock::db(), $row['id']],
        );

        return $claimed === 1 ? (int) $row['user_id'] : null;
    }
}
