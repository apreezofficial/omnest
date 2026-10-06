<?php

declare(strict_types=1);

namespace Omnest\Http;

use Closure;
use InvalidArgumentException;
use Omnest\Exceptions\HttpException;
use Omnest\Http\Middleware\Middleware;

/**
 * Small router: method + path with {params}, prefix/middleware groups.
 *
 *   $router->group(['prefix' => '/api/v1', 'middleware' => ['auth.parent']], function (Router $r) {
 *       $r->get('/children/{id}', [ChildController::class, 'show']);
 *   });
 *
 * Handlers are closures or [ControllerClass, 'method'] pairs; controllers are built
 * by the resolver passed to the constructor so they can receive dependencies.
 */
final class Router
{
    /** @var list<array{method: string, regex: string, params: list<string>, handler: mixed, middleware: list<string>}> */
    private array $routes = [];

    private string $prefix = '';

    /** @var list<string> */
    private array $groupMiddleware = [];

    /** @var array<string, Closure(): Middleware> */
    private array $aliases = [];

    /** @param Closure(class-string): object $resolver */
    public function __construct(private readonly Closure $resolver)
    {
    }

    /** @param Closure(): Middleware $factory */
    public function alias(string $name, Closure $factory): void
    {
        $this->aliases[$name] = $factory;
    }

    /** @param list<string> $middleware */
    public function get(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    /** @param list<string> $middleware */
    public function post(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    /** @param list<string> $middleware */
    public function put(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    /** @param list<string> $middleware */
    public function patch(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('PATCH', $path, $handler, $middleware);
    }

    /** @param list<string> $middleware */
    public function delete(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    /**
     * @param array{prefix?: string, middleware?: list<string>} $options
     * @param callable(Router): void $routes
     */
    public function group(array $options, callable $routes): void
    {
        $previousPrefix = $this->prefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->prefix .= '/' . trim($options['prefix'] ?? '', '/');
        $this->prefix = rtrim($this->prefix, '/');
        $this->groupMiddleware = [...$this->groupMiddleware, ...($options['middleware'] ?? [])];

        $routes($this);

        $this->prefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    /** @param list<string> $middleware */
    private function add(string $method, string $path, mixed $handler, array $middleware): void
    {
        $full = '/' . trim($this->prefix . '/' . trim($path, '/'), '/');

        preg_match_all('/\{(\w+)\}/', $full, $m);
        $regex = '#^' . preg_replace('/\\\\\{(\w+)\\\\\}/', '([^/]+)', preg_quote($full, '#')) . '$#';

        $this->routes[] = [
            'method' => $method,
            'regex' => $regex,
            'params' => $m[1],
            'handler' => $handler,
            'middleware' => [...$this->groupMiddleware, ...$middleware],
        ];
    }

    public function dispatch(Request $request): Response
    {
        $methodMismatch = false;
        // HEAD is answered by GET routes; the body is dropped by the web server.
        $method = $request->method === 'HEAD' ? 'GET' : $request->method;

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $request->path, $matches) !== 1) {
                continue;
            }
            if ($route['method'] !== $method) {
                $methodMismatch = true;
                continue;
            }

            $params = array_combine($route['params'], array_map('rawurldecode', array_slice($matches, 1)));
            $request = $request->withParams($params);

            return Pipeline::run(
                array_map(fn (string $name) => $this->resolveMiddleware($name), $route['middleware']),
                $request,
                fn (Request $r): Response => $this->callHandler($route['handler'], $r),
            );
        }

        throw $methodMismatch ? HttpException::methodNotAllowed() : HttpException::notFound('Route not found.');
    }

    private function resolveMiddleware(string $name): Middleware
    {
        if (!isset($this->aliases[$name])) {
            throw new InvalidArgumentException("Unknown middleware alias [$name].");
        }

        return ($this->aliases[$name])();
    }

    private function callHandler(mixed $handler, Request $request): Response
    {
        if (is_array($handler) && count($handler) === 2 && is_string($handler[0])) {
            $controller = ($this->resolver)($handler[0]);
            $handler = [$controller, $handler[1]];
        }

        if (!is_callable($handler)) {
            throw new InvalidArgumentException('Route handler is not callable.');
        }

        return $handler($request);
    }
}
