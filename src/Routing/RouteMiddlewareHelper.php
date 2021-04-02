<?php

namespace Rico\Swagger\Routing;

use Illuminate\Auth\Middleware\Authenticate as AuthenticateMiddleware;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\MiddlewareNameResolver;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Laravel\Passport\Http\Middleware\CheckClientCredentials as CheckClientCredentialsMiddleware;
use Laravel\Passport\Http\Middleware\CheckForAnyScope as CheckForAnyScopeMiddleware;
use Laravel\Passport\Http\Middleware\CheckScopes as CheckScopesMiddleware;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Class RouteMiddlewareResolver
 *
 * @package Rico\Swagger\Routing
 */
class RouteMiddlewareHelper
{
    private Kernel $kernel;
    private Router $router;
    private array $middleware;
    private array $middlewareGroups;

    /**
     * RouteMiddlewareResolver constructor.
     *
     * @param Kernel $kernel
     * @param Router $router
     *
     * @throws ReflectionException
     */
    public function __construct(Kernel $kernel, Router $router)
    {
        $this->kernel = $kernel;
        $this->router = $router;

        $middleware = new ReflectionProperty($this->router, 'middleware');
        $middleware->setAccessible(true);
        $this->middleware = $middleware->getValue($router);

        $middlewareGroups = new ReflectionProperty($this->router, 'middlewareGroups');
        $middlewareGroups->setAccessible(true);
        $this->middlewareGroups = $middlewareGroups->getValue($router);
    }

    /**
     * @param Route $route
     *
     * @return string[]
     */
    public function resolveMiddleware(Route $route): array
    {
        return $this->router->gatherRouteMiddleware($route);
    }

    /**
     * @param string $middleware
     * @param Route  $route
     *
     * @return bool
     */
    public function hasMiddleware(string $middleware, Route $route): bool
    {
        $expectedMiddleware = (array)MiddlewareNameResolver::resolve($middleware, $this->middleware, $this->middlewareGroups);
        $routeMiddleware = $this->router->gatherRouteMiddleware($route);

        foreach ($expectedMiddleware as $expected) {
            $met = false;

            foreach ($routeMiddleware as $middleware) {
                if (Str::is($expected, $middleware)) {
                    $met = true;
                    break;
                }
            }

            if (!$met) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $middlewareSelector
     * @param Route  $route
     *
     * @return array|null
     * @throws ReflectionException
     */
    public function getParsedMiddleware(string $middlewareSelector, Route $route): ?array
    {
        $expectedMiddleware = (string)MiddlewareNameResolver::resolve($middlewareSelector, $this->middleware, $this->middlewareGroups);
        $routeMiddleware = $this->router->gatherRouteMiddleware($route);

        foreach ($routeMiddleware as $middleware) {
            if (Str::is($expectedMiddleware, $middleware)) {
                return $this->parse($middleware);
            }
        }

        return null;
    }

    /**
     * @param Route $route
     *
     * @return array
     * @throws ReflectionException
     */
    public function oauthInfo(Route $route): array
    {
        $scopesMiddleware = $this->getParsedMiddleware(CheckScopesMiddleware::class . '*', $route);
        $scopeMiddleware = $this->getParsedMiddleware(CheckForAnyScopeMiddleware::class . '*', $route);
        $clientCredentialsMiddleware = $this->getParsedMiddleware(CheckClientCredentialsMiddleware::class . '*', $route);
        $authenticateMiddleware = $this->getParsedMiddleware(AuthenticateMiddleware::class . '*', $route);

        switch (true) {
            case !empty($clientCredentialsMiddleware):
                return [
                    'enabled' => true,
                    'scopes'  => $clientCredentialsMiddleware[1],
                ];
            case !empty($scopesMiddleware):
                return [
                    'enabled' => true,
                    'scopes'  => $scopesMiddleware[1],
                ];
            case !empty($scopeMiddleware):
                return [
                    'enabled' => true,
                    'scopes'  => $scopeMiddleware[1],
                ];
            case !empty($authenticateMiddleware):
                $guards = $authenticateMiddleware[1];

                if (empty($guards)) {
                    $guards = [Config::get('auth.defaults.guard')];
                }

                foreach ($guards as $guard) {
                    if (Config::get("auth.guards.{$guard}.driver") === 'passport') {
                        return [
                            'enabled' => true,
                            'scopes'  => [],
                        ];
                    }
                }

                return [
                    'enabled' => false,
                    'scopes'  => [],
                ];
            default:
                return [
                    'enabled' => false,
                    'scopes'  => [],
                ];
        }
    }

    /**
     * @param string $middleware
     *
     * @return array
     * @throws ReflectionException
     */
    protected function parse(string $middleware): array
    {
        $method = new ReflectionMethod(Pipeline::class, 'parsePipeString');
        $method->setAccessible(true);

        return $method->invoke(app(Pipeline::class), $middleware);
    }
}