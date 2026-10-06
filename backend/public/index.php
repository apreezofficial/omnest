<?php

declare(strict_types=1);

use Omnest\App;
use Omnest\Http\Request;
use Omnest\Http\Response;

require __DIR__ . '/../vendor/autoload.php';

// Last resort if something fails before the ErrorHandler middleware is running.
set_exception_handler(static function (Throwable $e): void {
    error_log((string) $e);
    Response::error(500, 'server_error', 'Something went wrong on our side.')->send();
});

App::boot(dirname(__DIR__))->handle(Request::fromGlobals())->send();
