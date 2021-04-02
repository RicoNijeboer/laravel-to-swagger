<?php

namespace Rico\Swagger\Actions;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\LazyCollection;
use ReflectionException;
use Rico\Reader\Endpoints\EndpointReader;
use Rico\Reader\Exceptions\EndpointDoesntExistException;
use Rico\Swagger\Exceptions\UnsupportedSwaggerExportTypeException;
use Rico\Swagger\Support\RouteFilter;
use Rico\Swagger\Swagger\Builder;
use Rico\Swagger\Swagger\Server;
use Rico\Swagger\Swagger\Tag;

/**
 * Class RouterToSwaggerAction
 *
 * @package Rico\Swagger\Actions
 */
class RouterToSwaggerAction
{
    const TYPE_YAML = 10;
    const TYPE_JSON = 20;
    const TYPE_ARRAY = 30;

    /**
     * Convert the router to the provided type.
     *
     * @param Router        $router
     * @param string|null   $title
     * @param string|null   $description
     * @param string|null   $version
     * @param Server[]      $servers
     * @param Tag[]         $tags
     * @param RouteFilter[] $exclude
     * @param RouteFilter[] $include
     * @param int           $type
     * @param array         $oauthConfig
     *
     * @return string|array
     * @throws UnsupportedSwaggerExportTypeException
     * @throws ReflectionException
     */
    public function convert(
        Router $router,
        ?string $title = null,
        ?string $description = null,
        ?string $version = null,
        array $servers = [],
        array $tags = [],
        array $include = [],
        array $exclude = [],
        int $type = self::TYPE_YAML,
        array $oauthConfig = ['enabled' => false]
    ) {
        $this->validateType($type);
        $swagger = new Builder($title, $description, $version, $tags);

        $this->addServers($swagger, $servers);
        $swagger->oauth($oauthConfig);

        $this->routes($router, $include, $exclude)
            ->each(fn (array $data) => $swagger->addPath(...$data));

        return $this->export($swagger, $type);
    }

    /**
     * Read a route.
     *
     * @param Route $route
     *
     * @return array
     * @throws BindingResolutionException
     * @throws EndpointDoesntExistException
     */
    public function readRoute(Route $route): array
    {
        return EndpointReader::readRoute($route, $route->methods())
            ->all();
    }

    /**
     * @param Router $router
     * @param array  $include
     * @param array  $exclude
     *
     * @return LazyCollection
     */
    protected function routes(Router $router, array $include = [], array $exclude = []): LazyCollection
    {
        $include = collect($include);
        $exclude = collect($exclude);

        return LazyCollection::make(function () use ($include, $exclude, $router) {
            $routes = $router->getRoutes()->getRoutes();

            for ($i = 0; $i < count($routes); $i++) {
                $route = $routes[$i];

                if (
                    !$exclude->some(fn (RouteFilter $filter) => $filter->matchesRoute($route))
                    && (
                        $include->isEmpty()
                        || $include->some(fn (RouteFilter $filter) => $filter->matchesRoute($route))
                    )
                ) {
                    yield $route;
                }
            }
        })
            ->map(fn (Route $route) => [
                $route->uri(),
                $this->readRoute($route),
                $route,
            ]);
    }

    /**
     * @param int $type
     *
     * @throws UnsupportedSwaggerExportTypeException
     */
    protected function validateType(int $type): void
    {
        switch ($type) {
            case self::TYPE_JSON:
            case self::TYPE_YAML:
            case self::TYPE_ARRAY:
                break;
            default:
                throw new UnsupportedSwaggerExportTypeException($type);
        }
    }

    /**
     * Export the current Swagger builder to the provided type.
     *
     * @param Builder $swagger
     * @param int     $type
     *
     * @return string|array
     * @throws UnsupportedSwaggerExportTypeException
     */
    protected function export(Builder $swagger, int $type)
    {
        $this->validateType($type);

        switch ($type) {
            case self::TYPE_JSON:
                return $swagger->toJson();
            case self::TYPE_YAML:
                return $swagger->toYaml();
            case self::TYPE_ARRAY:
            default:
                return $swagger->toArray();
        }
    }

    /**
     * Add the servers.
     *
     * @param Builder  $swagger
     * @param Server[] $servers
     */
    protected function addServers(Builder $swagger, array $servers): void
    {
        array_walk($servers, fn (Server $server) => $swagger->addServer($server));
    }
}