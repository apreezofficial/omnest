<?php

declare(strict_types=1);

namespace Omnest\Http\Middleware;

use Omnest\Http\Request;
use Omnest\Http\Response;

interface Middleware
{
    /** @param callable(Request): Response $next */
    public function process(Request $request, callable $next): Response;
}
