<?php

declare(strict_types=1);

use Omnest\Support\Env;

return [
    'env' => Env::get('APP_ENV', 'production'),
    'debug' => Env::bool('APP_DEBUG', false),
    'url' => Env::get('APP_URL', 'http://localhost:8000'),
    // Secret used to HMAC pairing codes. Generate with: php -r "echo bin2hex(random_bytes(32));"
    'app_key' => Env::get('APP_KEY', ''),
    // Where links in emails point (verify email, reset password).
    'dashboard_url' => Env::get('DASHBOARD_URL', 'http://localhost:3000'),
    'cors_origins' => Env::list('CORS_ALLOWED_ORIGINS'),
    // Used for a child's "today" until their phone reports its own timezone.
    'default_timezone' => Env::get('DEFAULT_TIMEZONE', 'Africa/Lagos'),

    'db' => [
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'port' => (int) Env::get('DB_PORT', '3306'),
        'database' => Env::get('DB_DATABASE', 'omnest'),
        'username' => Env::get('DB_USERNAME', 'root'),
        'password' => Env::get('DB_PASSWORD', ''),
    ],

    'mail' => [
        'driver' => Env::get('MAIL_DRIVER', 'log'), // log | resend
        'from' => Env::get('MAIL_FROM', 'Omnest <hello@omnest.app>'),
        'resend_key' => Env::get('RESEND_API_KEY', ''),
    ],

    'log' => [
        'level' => Env::get('LOG_LEVEL', 'info'),
        'path' => Env::get('LOG_PATH', 'storage/logs/app.log'),
    ],
];
