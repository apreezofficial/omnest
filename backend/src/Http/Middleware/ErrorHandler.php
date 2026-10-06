<?php

declare(strict_types=1);

namespace Omnest\Http\Middleware;

use Omnest\Exceptions\HttpException;
use Omnest\Http\Request;
use Omnest\Http\Response;
use Psr\Log\LoggerInterface;
use Throwable;

/** Turns every exception into the standard JSON error format. */
final class ErrorHandler implements Middleware
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly bool $debug = false,
    ) {
    }

    public function process(Request $request, callable $next): Response
    {
        try {
            return $next($request);
        } catch (HttpException $e) {
            if ($e->status >= 500) {
                $this->logger->error($e->getMessage(), ['exception' => $e]);
            }
            $response = Response::error($e->status, $e->errorCode, $e->getMessage(), $e->details);
            if ($e->status === 429 && isset($e->details['retry_after'])) {
                $response = $response->withHeader('Retry-After', (string) $e->details['retry_after']);
            }

            return $response;
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), [
                'exception' => $e,
                'method' => $request->method,
                'path' => $request->path,
            ]);

            $details = $this->debug
                ? ['exception' => $e::class, 'message' => $e->getMessage(), 'at' => $e->getFile() . ':' . $e->getLine()]
                : null;

            return Response::error(500, 'server_error', 'Something went wrong on our side.', $details);
        }
    }
}
