<?php

declare(strict_types=1);

namespace Omnest\Repositories;

use Omnest\Auth\Tokens;
use Omnest\Database\Database;
use Omnest\Support\Clock;

final class DeviceRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /**
     * Creates a paired device and returns [id, plain device token].
     *
     * @param array{name: string, model?: ?string, os_version?: ?string, app_version?: ?string, fcm_token?: ?string} $info
     * @return array{id: int, token: string}
     */
    public function create(int $childId, array $info): array
    {
        $token = Tokens::generate(Tokens::DEVICE);
        $now = Clock::db();
        $fcm = $info['fcm_token'] ?? null;

        $id = $this->db->insert('devices', [
            'child_id' => $childId,
            'token_hash' => $token['hash'],
            'name' => $info['name'],
            'model' => $info['model'] ?? null,
            'os_version' => $info['os_version'] ?? null,
            'app_version' => $info['app_version'] ?? null,
            'fcm_token' => $fcm,
            'fcm_token_updated_at' => $fcm !== null ? $now : null,
            'last_seen_at' => $now,
            'paired_at' => $now,
        ]);

        return ['id' => $id, 'token' => $token['plain']];
    }

    /**
     * Active device + child + parent ids for a device token.
     *
     * @return array<string, mixed>|null
     */
    public function findActiveByToken(string $plain): ?array
    {
        return $this->db->first(
            'SELECT d.*, c.user_id, c.name AS child_name, c.age_tier
             FROM devices d JOIN children c ON c.id = d.child_id
             WHERE d.token_hash = ? AND d.revoked_at IS NULL',
            [Tokens::hash($plain)],
        );
    }

    /** Updates last_seen_at at most once a minute. */
    public function touch(int $deviceId, ?string $lastSeenAt): void
    {
        if ($lastSeenAt !== null && $lastSeenAt > Clock::db('-1 minute')) {
            return;
        }
        $this->db->execute('UPDATE devices SET last_seen_at = ? WHERE id = ?', [Clock::db(), $deviceId]);
    }

    public function updateFcmToken(int $deviceId, string $fcmToken): void
    {
        $this->db->execute(
            'UPDATE devices SET fcm_token = ?, fcm_token_updated_at = ? WHERE id = ?',
            [$fcmToken, Clock::db(), $deviceId],
        );
    }

    /** @return list<array<string, mixed>> active devices of a child */
    public function forChild(int $childId): array
    {
        return $this->db->select(
            'SELECT * FROM devices WHERE child_id = ? AND revoked_at IS NULL ORDER BY paired_at DESC',
            [$childId],
        );
    }

    /**
     * Active device owned (through its child) by this parent.
     *
     * @return array<string, mixed>|null
     */
    public function findForUser(int $deviceId, int $userId): ?array
    {
        return $this->db->first(
            'SELECT d.* FROM devices d JOIN children c ON c.id = d.child_id
             WHERE d.id = ? AND c.user_id = ? AND d.revoked_at IS NULL',
            [$deviceId, $userId],
        );
    }

    public function revoke(int $deviceId): void
    {
        $this->db->execute(
            'UPDATE devices SET revoked_at = ?, fcm_token = NULL WHERE id = ? AND revoked_at IS NULL',
            [Clock::db(), $deviceId],
        );
    }

    /**
     * @param array<string, mixed> $device
     * @return array<string, mixed>
     */
    public static function present(array $device): array
    {
        return [
            'id' => (int) $device['id'],
            'child_id' => (int) $device['child_id'],
            'name' => $device['name'],
            'model' => $device['model'],
            'os_version' => $device['os_version'],
            'app_version' => $device['app_version'],
            'push_enabled' => $device['fcm_token'] !== null,
            'last_seen_at' => Clock::iso($device['last_seen_at']),
            'paired_at' => Clock::iso($device['paired_at']),
        ];
    }
}
