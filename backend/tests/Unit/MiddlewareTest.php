<?php

declare(strict_types=1);

namespace Omnest\Tests\Unit;

use Omnest\Auth\TokenResolver;
use Omnest\Exceptions\HttpException;
use Omnest\Http\Middleware\Authenticate;
use Omnest\Http\Middleware\Cors;
use Omnest\Http\Middleware\JsonBody;
use Omnest\Http\Middleware\RateLimit;
use Omnest\Http\Request;
use Omnest\Http\Response;
use Omnest\Support\InMemoryRateLimiter;
use PHPUnit\Framework\TestCase;

final class MiddlewareTest extends TestCase
{
    private static function echoBody(): \Closure
    {
        return fn (Request $r) => Response::success($r->all());
    }

    public function testJsonBodyParsesObjects(): void
    {
        $req = new Request('POST', '/x', ['content-type' => 'application/json'], [], '{"a":1}');

        self::assertSame(['a' => 1], (new JsonBody())->process($req, self::echoBody())->decoded()['data']);
    }

    public function testJsonBodyRejectsMalformedAndWrongType(): void
    {
        $bad = new Request('POST', '/x', ['content-type' => 'application/json'], [], '{oops');
        try {
            (new JsonBody())->process($bad, self::echoBody());
            self::fail();
        } catch (HttpException $e) {
            self::assertSame(400, $e->status);
        }

        $form = new Request('POST', '/x', ['content-type' => 'text/plain'], [], 'a=1');
        try {
            (new JsonBody())->process($form, self::echoBody());
            self::fail();
        } catch (HttpException $e) {
            self::assertSame(415, $e->status);
        }
    }

    public function testCorsAnswersPreflightForAllowedOrigin(): void
    {
        $cors = new Cors(['http://localhost:3000']);
        $req = new Request('OPTIONS', '/api/v1/children', ['origin' => 'http://localhost:3000']);

        $res = $cors->process($req, fn () => self::fail('Preflight must not reach the app'));

        self::assertSame(204, $res->status);
        self::assertSame('http://localhost:3000', $res->header('Access-Control-Allow-Origin'));
    }

    public function testCorsIgnoresUnknownOrigin(): void
    {
        $res = (new Cors(['http://localhost:3000']))
            ->process(new Request('GET', '/', ['origin' => 'https://evil.example']), self::echoBody());

        self::assertNull($res->header('Access-Control-Allow-Origin'));
    }

    public function testRateLimitBlocksAfterMax(): void
    {
        $limiter = new InMemoryRateLimiter(fn () => 1_000_000);
        $mw = new RateLimit($limiter, 'test', 2, 60);
        $req = new Request('GET', '/', [], [], '', '10.0.0.1');

        $mw->process($req, self::echoBody());
        self::assertSame('0', $mw->process($req, self::echoBody())->header('X-RateLimit-Remaining'));

        try {
            $mw->process($req, self::echoBody());
            self::fail();
        } catch (HttpException $e) {
            self::assertSame(429, $e->status);
            self::assertSame(20, $e->details['retry_after']); // 1_000_000 % 60 = 40 seconds into the window
        }
    }

    public function testAuthenticateAttachesPrincipal(): void
    {
        $resolver = new class implements TokenResolver {
            public function resolve(string $plainToken): ?array
            {
                return $plainToken === 'good' ? ['id' => 7] : null;
            }
        };
        $mw = new Authenticate($resolver, 'user');

        $res = $mw->process(
            new Request('GET', '/', ['authorization' => 'Bearer good']),
            fn (Request $r) => Response::success($r->attribute('user')),
        );
        self::assertSame(['id' => 7], $res->decoded()['data']);

        $this->expectExceptionObject(HttpException::unauthorized('Your session has expired. Sign in again.'));
        $mw->process(new Request('GET', '/', ['authorization' => 'Bearer bad']), self::echoBody());
    }
}
