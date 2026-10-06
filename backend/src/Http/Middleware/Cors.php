<?php

declare(strict_types=1);

namespace Omnest\Http\Middleware;

use Omnest\Http\Request;
use Omnest\Http\Response;

final class Cors implements Middleware
{
    /** @param list<string> $allowedOrigins */
    public function __construct(private readonly array $allowedOrigins)
    {
    }

    public function process(Request $request, callable $next): Response
    {
        $origin = $request->header('origin');
        $allowed = $origin !== null && in_array($origin, $this->allowedOrigins, true);

        $response = $request->method === 'OPTIONS' ? Response::noContent() : $next($request);

        if (!$allowed) {
            return $response;
        }

        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Vary', 'Origin');

        if ($request->method === 'OPTIONS') {
            $response = $response
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, If-None-Match')
                ->withHeader('Access-Control-Max-Age', '600');
        }

        return $response;
    }
}
