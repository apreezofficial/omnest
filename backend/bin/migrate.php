<?php

declare(strict_types=1);

use Omnest\App;
use Omnest\Database\Database;
use Omnest\Database\Migrator;

require __DIR__ . '/../vendor/autoload.php';

$app = App::boot(dirname(__DIR__));
$migrator = new Migrator($app->container->get(Database::class), $app->basePath . '/migrations');

$ran = $migrator->migrate(static fn (string $name) => fwrite(STDOUT, "Migrated: $name\n"));

fwrite(STDOUT, $ran === [] ? "Nothing to migrate.\n" : sprintf("Done, %d migration(s).\n", count($ran)));
