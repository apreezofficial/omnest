<?php

declare(strict_types=1);

namespace Omnest\Tests\Unit;

use Omnest\Database\Migrator;
use PHPUnit\Framework\TestCase;

final class MigratorTest extends TestCase
{
    public function testSplitsStatementsAndDropsComments(): void
    {
        $sql = <<<SQL
            -- users
            CREATE TABLE a (id INT);
            -- note; with a semicolon
            CREATE TABLE b (
                note VARCHAR(20) DEFAULT 'x;y'
            );
            SQL;

        $statements = Migrator::splitStatements($sql);

        self::assertCount(2, $statements);
        self::assertSame('CREATE TABLE a (id INT)', $statements[0]);
        self::assertStringContainsString("'x;y'", $statements[1]);
    }
}
