<?php

declare(strict_types=1);

namespace Omnest\Tests\Unit;

use Omnest\Exceptions\HttpException;
use Omnest\Http\Middleware\Middleware;
use Omnest\Http\Request;
use Omnest\Http\Response;
use Omnest\Http\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private function router(): Router
    {
        return new Router(fn (string $class) => new $class());
    }

    public function testMatchesPathParams(): void
    {
        $router = $this->router();
        $router->get('/children/{id}/devices/{deviceId}', fn (Request $r) => Response::success([
            'id' => $r->param('id'),
            'device' => $r->param('deviceId'),
        ]));

        $res = $router->dispatch(new Request('GET', '/children/42/devices/abc'));

        self::assertSame(['id' => '42', 'device' => 'abc'], $res->decoded()['data']);
    }

    public function testGroupsApplyPrefixAndMiddlewareInOrder(): void
    {
        $router = $this->router();
        $trace = new \ArrayObject();
        $tag = fn (string $name) => fn () => new class ($name, $trace) implements Middleware {
            public function __construct(private string $name, private \ArrayObject $trace)
            {
            }

            public function process(Request $request, callable $next): Response
            {
                $this->trace[] = $this->name;

                return $next($request);
            }
        };
        $router->alias('outer', $tag('outer'));
        $router->alias('inner', $tag('inner'));

        $router->group(['prefix' => '/api/v1', 'middleware' => ['outer']], function (Router $r) {
            $r->group(['prefix' => 'kids', 'middleware' => ['inner']], function (Router $r) {
                $r->post('/', fn () => Response::created(['ok' => true]));
            });
        });

        $res = $router->dispatch(new Request('POST', '/api/v1/kids'));

        self::assertSame(201, $res->status);
        self::assertSame(['outer', 'inner'], $trace->getArrayCopy());
    }

    public function testUnknownRouteIs404(): void
    {
        $this->expectExceptionObject(HttpException::notFound('Route not found.'));
        $this->router()->dispatch(new Request('GET', '/nope'));
    }

    public function testWrongMethodIs405(): void
    {
        $router = $this->router();
        $router->get('/health', fn () => Response::success());

        try {
            $router->dispatch(new Request('DELETE', '/health'));
            self::fail('Expected 405');
        } catch (HttpException $e) {
            self::assertSame(405, $e->status);
        }
    }

    public function testControllerPairIsResolved(): void
    {
        $router = $this->router();
        $router->get('/health', [\Omnest\Controllers\HealthController::class, 'show']);

        self::assertSame('ok', $router->dispatch(new Request('GET', '/health'))->decoded()['data']['status']);
    }
}
