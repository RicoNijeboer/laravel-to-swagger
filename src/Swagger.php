<?php

namespace RicoNijeboer\Swagger;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route as RouteFacade;
use RicoNijeboer\Swagger\Http\Routing\RouteRegistrar;

/**
 * Class Swagger
 *
 * @package RicoNijeboer\Swagger
 */
class Swagger
{
    protected static Route $route;

    public static function routes($callback = null, array $options = []): void
    {
        $callback = $callback ?? fn (RouteRegistrar $registrar) => $registrar->forDocumentation();

        $defaultOptions = [
            'as'        => 'swagger.',
            'namespace' => '\Rico\Swagger\Http\Controllers',
        ];

        $options = array_merge($defaultOptions, $options);

        RouteFacade::group($options, function (Router $router) use ($callback) {
            $callback(new RouteRegistrar($router));
        });
    }

    /**
     * @param Route|null $route
     *
     * @return Route
     */
    public static function configRoute(Route $route = null): Route
    {
        static::$route = $route ?? static::$route;

        return static::$route;
    }
}
