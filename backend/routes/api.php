<?php

declare(strict_types=1);

use Omnest\Controllers\HealthController;
use Omnest\Http\Router;

return static function (Router $router): void {
    $router->get('/health', [HealthController::class, 'show']);

    $router->group(['prefix' => '/api/v1', 'middleware' => ['throttle.api']], static function (Router $r): void {
        $r->get('/health', [HealthController::class, 'show']);

        // Phase 1: auth, children, pairing
        // Phase 2+: usage, rules, knock, commands
    });
};
