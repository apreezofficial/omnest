<?php

declare(strict_types=1);

namespace Omnest\Database;

/**
 * Runs numbered SQL files (0001_name.sql, 0002_name.sql, ...) once each,
 * recording them in the `migrations` table.
 */
final class Migrator
{
    public function __construct(
        private readonly Database $db,
        private readonly string $directory,
    ) {
    }

    /** @return list<string> names of migrations that ran */
    public function migrate(?callable $onRun = null): array
    {
        $this->ensureTable();

        $done = array_column($this->db->select('SELECT name FROM migrations'), 'name');
        $batch = (int) $this->db->scalar('SELECT COALESCE(MAX(batch), 0) FROM migrations') + 1;
        $ran = [];

        foreach ($this->pending($done) as $name => $path) {
            foreach (self::splitStatements((string) file_get_contents($path)) as $statement) {
                $this->db->execute($statement);
            }
            $this->db->insert('migrations', ['name' => $name, 'batch' => $batch]);
            $ran[] = $name;
            if ($onRun) {
                $onRun($name);
            }
        }

        return $ran;
    }

    /**
     * @param list<string> $done
     * @return array<string, string> name => path, in order
     */
    private function pending(array $done): array
    {
        $files = glob(rtrim($this->directory, '/\\') . '/*.sql') ?: [];
        sort($files, SORT_STRING);

        $pending = [];
        foreach ($files as $path) {
            $name = basename($path, '.sql');
            if (!in_array($name, $done, true)) {
                $pending[$name] = $path;
            }
        }

        return $pending;
    }

    private function ensureTable(): void
    {
        $this->db->execute(
            'CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(191) NOT NULL UNIQUE,
                batch INT UNSIGNED NOT NULL,
                ran_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /**
     * Splits a SQL file on semicolons at line ends, dropping `--` comment lines.
     *
     * @return list<string>
     */
    public static function splitStatements(string $sql): array
    {
        $lines = array_filter(
            preg_split('/\R/', $sql) ?: [],
            static fn (string $line) => !str_starts_with(ltrim($line), '--'),
        );
        $parts = preg_split('/;\s*$/m', implode("\n", $lines)) ?: [];

        return array_values(array_filter(array_map('trim', $parts), static fn (string $s) => $s !== ''));
    }
}
