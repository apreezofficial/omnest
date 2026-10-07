<?php

declare(strict_types=1);

namespace Omnest\Repositories;

use Omnest\Database\Database;
use Omnest\Support\Clock;

final class PairingCodeRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function isActiveHash(string $codeHash): bool
    {
        return $this->db->scalar(
            'SELECT 1 FROM pairing_codes WHERE code_hash = ? AND used_at IS NULL AND expires_at > ? LIMIT 1',
            [$codeHash, Clock::db()],
        ) !== null;
    }

    /** Expires any open codes for the child, then stores the new one. */
    public function replaceForChild(int $childId, string $codeHash, string $expiresAt): void
    {
        $this->db->execute(
            'UPDATE pairing_codes SET expires_at = ? WHERE child_id = ? AND used_at IS NULL AND expires_at > ?',
            [Clock::db(), $childId, Clock::db()],
        );
        $this->db->insert('pairing_codes', [
            'child_id' => $childId,
            'code_hash' => $codeHash,
            'expires_at' => $expiresAt,
            'created_at' => Clock::db(),
        ]);
    }

    /** @return array<string, mixed>|null */
    public function findActive(string $codeHash): ?array
    {
        return $this->db->first(
            'SELECT * FROM pairing_codes WHERE code_hash = ? AND used_at IS NULL AND expires_at > ? ORDER BY id DESC LIMIT 1',
            [$codeHash, Clock::db()],
        );
    }

    /** Claims the code; false if someone else used it first. */
    public function claim(int $id): bool
    {
        return $this->db->execute(
            'UPDATE pairing_codes SET used_at = ? WHERE id = ? AND used_at IS NULL',
            [Clock::db(), $id],
        ) === 1;
    }

    public function attachDevice(int $id, int $deviceId): void
    {
        $this->db->execute('UPDATE pairing_codes SET device_id = ? WHERE id = ?', [$deviceId, $id]);
    }
}
