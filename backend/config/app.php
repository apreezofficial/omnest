<?php

declare(strict_types=1);

use Omnest\Support\Env;

return [
    'env' => Env::get('APP_ENV', 'production'),
    'debug' => Env::bool('APP_DEBUG', false),
    'url' => Env::get('APP_URL', 'http://localhost:8000'),
    'cors_origins' => Env::list('CORS_ALLOWED_ORIGINS'),

    'db' => [
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'port' => (int) Env::get('DB_PORT', '3306'),
        'database' => Env::get('DB_DATABASE', 'omnest'),
        'username' => Env::get('DB_USERNAME', 'root'),
        'password' => Env::get('DB_PASSWORD', ''),
    ],

    'log' => [
        'level' => Env::get('LOG_LEVEL', 'info'),
        'path' => Env::get('LOG_PATH', 'storage/logs/app.log'),
    ],
];
