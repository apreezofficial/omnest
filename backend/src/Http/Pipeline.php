<?php

declare(strict_types=1);

namespace Omnest\Http;

use Omnest\Http\Middleware\Middleware;

final class Pipeline
{
    /**
     * Runs $request through $middleware (outermost first), ending at $core.
     *
     * @param list<Middleware>          $middleware
     * @param callable(Request): Response $core
     */
    public static function run(array $middleware, Request $request, callable $core): Response
    {
        $next = $core;
        foreach (array_reverse($middleware) as $layer) {
            $inner = $next;
            $next = static fn (Request $r): Response => $layer->process($r, $inner);
        }

        return $next($request);
    }
}
