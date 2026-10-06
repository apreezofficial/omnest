<?php

declare(strict_types=1);

namespace Omnest\Tests\Feature;

use Omnest\App;
use Omnest\Http\Request;
use Omnest\Support\InMemoryRateLimiter;
use Omnest\Support\RateLimiter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class AppTest extends TestCase
{
    private function app(bool $debug = false): App
    {
        $app = App::withConfig(dirname(__DIR__, 2), [
            'debug' => $debug,
            'cors_origins' => ['http://localhost:3000'],
            'db' => [],
            'log' => ['path' => 'php://stderr', 'level' => 'debug'],
        ]);
        $app->container->instance(LoggerInterface::class, new NullLogger());
        $app->container->instance(RateLimiter::class, new InMemoryRateLimiter());

        return $app;
    }

    public function testHealthUsesStandardEnvelopeAndSecurityHeaders(): void
    {
        $res = $this->app()->handle(new Request('GET', '/api/v1/health'));

        self::assertSame(200, $res->status);
        self::assertSame('ok', $res->decoded()['data']['status']);
        self::assertSame('nosniff', $res->header('X-Content-Type-Options'));
        self::assertSame('120', $res->header('X-RateLimit-Limit'));
    }

    public function testErrorsUseStandardEnvelope(): void
    {
        $res = $this->app()->handle(new Request('GET', '/api/v1/missing'));

        self::assertSame(404, $res->status);
        self::assertSame('not_found', $res->decoded()['error']['code']);
    }

    public function testMalformedJsonIs400(): void
    {
        $res = $this->app()->handle(new Request('POST', '/api/v1/health', ['content-type' => 'application/json'], [], '{'));

        self::assertSame(400, $res->status);
        self::assertSame('bad_request', $res->decoded()['error']['code']);
    }
}
