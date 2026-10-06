<?php

declare(strict_types=1);

namespace Omnest;

use Dotenv\Dotenv;
use Omnest\Database\Database;
use Omnest\Http\Middleware\Authenticate;
use Omnest\Http\Middleware\Cors;
use Omnest\Http\Middleware\ErrorHandler;
use Omnest\Http\Middleware\JsonBody;
use Omnest\Http\Middleware\RateLimit;
use Omnest\Http\Pipeline;
use Omnest\Http\Request;
use Omnest\Http\Response;
use Omnest\Http\Router;
use Omnest\Support\Container;
use Omnest\Support\DatabaseRateLimiter;
use Omnest\Support\LoggerFactory;
use Omnest\Support\RateLimiter;
use Psr\Log\LoggerInterface;

final class App
{
    public readonly Container $container;

    /** @param array<string, mixed> $config */
    private function __construct(public readonly string $basePath, public readonly array $config)
    {
        $this->container = new Container();
        $this->registerServices();
    }

    /** Loads .env (if present) and config/app.php. */
    public static function boot(string $basePath): self
    {
        if (is_file($basePath . '/.env')) {
            Dotenv::createImmutable($basePath)->safeLoad();
        }

        return new self($basePath, require $basePath . '/config/app.php');
    }

    /**
     * For tests: explicit config, services overridable via $app->container.
     *
     * @param array<string, mixed> $config
     */
    public static function withConfig(string $basePath, array $config): self
    {
        return new self($basePath, $config);
    }

    private function registerServices(): void
    {
        $c = $this->container;
        $c->instance(self::class, $this);

        $c->set(Database::class, fn () => Database::mysql($this->config['db']));
        $c->set(LoggerInterface::class, function () {
            $path = $this->config['log']['path'];
            if (!str_starts_with($path, '/') && preg_match('/^[A-Za-z]:[\\\\\/]/', $path) !== 1) {
                $path = $this->basePath . '/' . $path;
            }

            return LoggerFactory::create($path, $this->config['log']['level']);
        });
        $c->set(RateLimiter::class, fn (Container $c) => new DatabaseRateLimiter($c->get(Database::class)));

        $c->set(Router::class, function (Container $c) {
            $router = new Router(fn (string $class) => $c->get($class));

            $router->alias('auth.parent', fn () => new Authenticate($c->get('tokens.parent'), 'user'));
            $router->alias('auth.device', fn () => new Authenticate($c->get('tokens.device'), 'device'));
            $router->alias('throttle.auth', fn () => new RateLimit($c->get(RateLimiter::class), 'auth', 10, 60));
            $router->alias('throttle.api', fn () => new RateLimit($c->get(RateLimiter::class), 'api', 120, 60));

            (require $this->basePath . '/routes/api.php')($router);

            return $router;
        });
    }

    public function handle(Request $request): Response
    {
        $global = [
            new Cors($this->config['cors_origins']),
            new ErrorHandler($this->container->get(LoggerInterface::class), (bool) $this->config['debug']),
            new JsonBody(),
        ];

        $response = Pipeline::run(
            $global,
            $request,
            fn (Request $r) => $this->container->get(Router::class)->dispatch($r),
        );

        return $response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Referrer-Policy', 'no-referrer')
            ->withHeader('Cache-Control', $response->header('Cache-Control') ?? 'no-store');
    }
}
