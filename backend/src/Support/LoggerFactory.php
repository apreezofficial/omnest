<?php

declare(strict_types=1);

namespace Omnest\Support;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;

final class LoggerFactory
{
    public static function create(string $path, string $level = 'info'): Logger
    {
        $handler = new StreamHandler($path, Level::fromName(ucfirst(strtolower($level))));
        $handler->setFormatter(new JsonFormatter());

        // Error tracking (Sentry etc.) plugs in here as an extra handler at Level::Error.
        return (new Logger('omnest'))
            ->pushHandler($handler)
            ->pushProcessor(new PsrLogMessageProcessor());
    }
}
