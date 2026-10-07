<?php

declare(strict_types=1);

namespace Omnest\Repositories;

use Omnest\Database\Database;
use Omnest\Support\Clock;

/** Every read/write is scoped by the owning parent's user id. */
final class ChildRepository
{
    public const AGE_TIERS = ['kid', 'preteen', 'teen'];

    public function __construct(private readonly Database $db)
    {
    }

    /** @return list<array<string, mixed>> */
    public function allForUser(int $userId): array
    {
        return $this->db->select(
            'SELECT c.*,
                (SELECT COUNT(*) FROM devices d WHERE d.child_id = c.id AND d.revoked_at IS NULL) AS device_count
             FROM children c WHERE c.user_id = ? ORDER BY c.created_at, c.id',
            [$userId],
        );
    }

    /** @return array<string, mixed>|null */
    public function findForUser(int $childId, int $userId): ?array
    {
        return $this->db->first(
            'SELECT c.*,
                (SELECT COUNT(*) FROM devices d WHERE d.child_id = c.id AND d.revoked_at IS NULL) AS device_count
             FROM children c WHERE c.id = ? AND c.user_id = ?',
            [$childId, $userId],
        );
    }

    /** @return array<string, mixed>|null */
    public function find(int $childId): ?array
    {
        return $this->db->first('SELECT * FROM children WHERE id = ?', [$childId]);
    }

    /** @param array{name: string, age_tier: string, birth_date?: ?string, avatar?: ?string} $data */
    public function create(int $userId, array $data): int
    {
        $now = Clock::db();

        return $this->db->insert('children', [
            'user_id' => $userId,
            'name' => $data['name'],
            'age_tier' => $data['age_tier'],
            'birth_date' => $data['birth_date'] ?? null,
            'avatar' => $data['avatar'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /** @param array<string, mixed> $changes subset of name, age_tier, birth_date, avatar */
    public function update(int $childId, int $userId, array $changes): void
    {
        $allowed = array_intersect_key($changes, array_flip(['name', 'age_tier', 'birth_date', 'avatar']));
        if ($allowed === []) {
            return;
        }

        $sets = implode(', ', array_map(static fn (string $col) => "`$col` = ?", array_keys($allowed)));
        $this->db->execute(
            "UPDATE children SET $sets, updated_at = ? WHERE id = ? AND user_id = ?",
            [...array_values($allowed), Clock::db(), $childId, $userId],
        );
    }

    public function delete(int $childId, int $userId): void
    {
        $this->db->execute('DELETE FROM children WHERE id = ? AND user_id = ?', [$childId, $userId]);
    }

    /**
     * @param array<string, mixed> $child
     * @return array<string, mixed>
     */
    public static function present(array $child): array
    {
        return [
            'id' => (int) $child['id'],
            'name' => $child['name'],
            'age_tier' => $child['age_tier'],
            'birth_date' => $child['birth_date'],
            'avatar' => $child['avatar'],
            'device_count' => (int) ($child['device_count'] ?? 0),
            'created_at' => Clock::iso($child['created_at']),
        ];
    }
}
