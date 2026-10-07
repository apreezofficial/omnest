<?php

declare(strict_types=1);

namespace Omnest\Tests\Feature;

use Omnest\App;
use Omnest\Database\Database;
use Omnest\Database\Migrator;
use Omnest\Http\Request;
use Omnest\Http\Response;
use Omnest\Mail\ArrayMailer;
use Omnest\Mail\Mailer;
use Omnest\Support\Clock;
use Omnest\Support\InMemoryRateLimiter;
use Omnest\Support\RateLimiter;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Runs requests through the full app against a real MySQL/MariaDB test database.
 * Configure with TEST_DB_HOST / TEST_DB_PORT / TEST_DB_DATABASE / TEST_DB_USERNAME / TEST_DB_PASSWORD
 * (defaults: 127.0.0.1:3306, omnest_test, root, no password). Skipped when no database is reachable.
 */
abstract class ApiTestCase extends TestCase
{
    private static ?Database $db = null;
    private static bool $migrated = false;

    /** @var list<string> */
    private static array $tables = [];

    protected App $app;
    protected ArrayMailer $mailer;

    protected function setUp(): void
    {
        $db = self::database();
        if ($db === null) {
            self::markTestSkipped('No test database available.');
        }

        // DELETE, not TRUNCATE: truncate recreates table files, which is very slow on Windows.
        $db->execute('SET FOREIGN_KEY_CHECKS = 0');
        foreach (self::$tables as $table) {
            $db->execute("DELETE FROM `$table`");
        }
        $db->execute('SET FOREIGN_KEY_CHECKS = 1');

        $this->mailer = new ArrayMailer();
        $this->app = App::withConfig(dirname(__DIR__, 2), [
            'debug' => true,
            'app_key' => str_repeat('k', 64),
            'dashboard_url' => 'http://dash.test',
            'cors_origins' => [],
            'db' => [],
            'mail' => ['driver' => 'array'],
            'log' => ['path' => 'php://stderr', 'level' => 'debug'],
        ]);
        $this->app->container->instance(Database::class, $db);
        $this->app->container->instance(LoggerInterface::class, new NullLogger());
        $this->app->container->instance(RateLimiter::class, new InMemoryRateLimiter());
        $this->app->container->instance(Mailer::class, $this->mailer);
    }

    protected function tearDown(): void
    {
        Clock::unfreeze();
    }

    /** @param array<string, mixed>|null $body */
    protected function json(string $method, string $path, ?array $body = null, ?string $token = null, string $ip = '10.0.0.1'): Response
    {
        $headers = ['accept' => 'application/json'];
        if ($body !== null) {
            $headers['content-type'] = 'application/json';
        }
        if ($token !== null) {
            $headers['authorization'] = 'Bearer ' . $token;
        }

        return $this->app->handle(new Request(
            $method,
            $path,
            $headers,
            [],
            $body === null ? '' : json_encode($body, JSON_THROW_ON_ERROR),
            $ip,
        ));
    }

    protected function db(): Database
    {
        return self::database();
    }

    /** Registers a parent and returns their token. */
    protected function registerParent(string $email = 'ada@example.com', string $password = 'correct horse'): string
    {
        $res = $this->json('POST', '/api/v1/auth/register', ['name' => 'Ada', 'email' => $email, 'password' => $password]);
        self::assertSame(201, $res->status, $res->body);

        return $res->decoded()['data']['token'];
    }

    protected function createChild(string $token, string $name = 'Tobi', string $tier = 'kid'): int
    {
        $res = $this->json('POST', '/api/v1/children', ['name' => $name, 'age_tier' => $tier], $token);
        self::assertSame(201, $res->status, $res->body);

        return $res->decoded()['data']['id'];
    }

    /** Pulls the ?token= value out of the last email's link. */
    protected function tokenFromLastEmail(): string
    {
        $text = $this->mailer->last()?->text ?? '';
        self::assertMatchesRegularExpression('/\?token=(omx_[a-f0-9]+)/', $text);
        preg_match('/\?token=(omx_[a-f0-9]+)/', $text, $m);

        return $m[1];
    }

    private static function database(): ?Database
    {
        if (self::$db !== null) {
            return self::$db;
        }

        $env = static fn (string $k, string $d) => getenv($k) !== false ? (string) getenv($k) : $d;
        try {
            $pdo = new PDO(
                sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $env('TEST_DB_HOST', '127.0.0.1'),
                    $env('TEST_DB_PORT', '3306'),
                    $env('TEST_DB_DATABASE', 'omnest_test'),
                ),
                $env('TEST_DB_USERNAME', 'root'),
                $env('TEST_DB_PASSWORD', ''),
                [PDO::ATTR_TIMEOUT => 3, PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00'"],
            );
        } catch (PDOException) {
            return null;
        }

        self::$db = Database::fromPdo($pdo);

        if (!self::$migrated) {
            // Fresh schema once per test run.
            self::$db->execute('SET FOREIGN_KEY_CHECKS = 0');
            foreach (self::$db->select('SELECT table_name AS t FROM information_schema.tables WHERE table_schema = DATABASE()') as $row) {
                self::$db->execute('DROP TABLE `' . $row['t'] . '`');
            }
            self::$db->execute('SET FOREIGN_KEY_CHECKS = 1');
            (new Migrator(self::$db, dirname(__DIR__, 2) . '/migrations'))->migrate();
            self::$migrated = true;
            self::$tables = array_column(
                self::$db->select("SELECT table_name AS t FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name <> 'migrations'"),
                't',
            );
        }

        return self::$db;
    }
}
