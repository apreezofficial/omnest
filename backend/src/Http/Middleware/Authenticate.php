<?php

declare(strict_types=1);

namespace Omnest\Http\Middleware;

use Omnest\Auth\TokenResolver;
use Omnest\Exceptions\HttpException;
use Omnest\Http\Request;
use Omnest\Http\Response;

/**
 * Bearer-token auth. One instance per token kind:
 *   new Authenticate($parentTokens, 'user')    -> auth.parent
 *   new Authenticate($deviceTokens, 'device')  -> auth.device
 * The resolved principal is attached to the request under $attribute.
 */
final class Authenticate implements Middleware
{
    public function __construct(
        private readonly TokenResolver $resolver,
        private readonly string $attribute,
    ) {
    }

    public function process(Request $request, callable $next): Response
    {
        $token = $request->bearerToken();
        if ($token === null) {
            throw HttpException::unauthorized();
        }

        $principal = $this->resolver->resolve($token);
        if ($principal === null) {
            throw HttpException::unauthorized('Your session has expired. Sign in again.');
        }

        return $next($request->withAttribute($this->attribute, $principal));
    }
}
