<?php

declare(strict_types=1);

// Daily housekeeping (usage retention, expired tokens/codes/rate-limit buckets).
// Cron: 15 3 * * * php /path/to/backend/bin/cleanup.php

use Omnest\App;
use Omnest\Services\Maintenance;

require __DIR__ . '/../vendor/autoload.php';

$app = App::boot(dirname(__DIR__));
$deleted = $app->container->get(Maintenance::class)->run();

foreach ($deleted as $kind => $count) {
    fwrite(STDOUT, sprintf("%-14s %d\n", $kind, $count));
}
