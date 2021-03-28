<?php

namespace Rico\Swagger\Actions;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Rico\Reader\Endpoints\EndpointReader;
use Rico\Reader\Exceptions\EndpointDoesntExistException;
use Rico\Swagger\Exceptions\UnsupportedSwaggerExportTypeException;
use Rico\Swagger\Support\RouteFilter;
use Rico\Swagger\Swagger\Builder;
use Rico\Swagger\Swagger\Server;
use Rico\Swagger\Swagger\Tag;

use function array_walk;

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
    private Router $router;
    private Builder $swagger;

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
     *
     * @return string|array
     * @throws BindingResolutionException
     * @throws EndpointDoesntExistException
     * @throws UnsupportedSwaggerExportTypeException
     */
    public function convert(
        Router $router,
        ?string $title = null,
        ?string $description = null,
        ?string $version = null,
        array $servers = [],
        array $tags = [],
        array $exclude = [],
        array $include = [],
        int $type = self::TYPE_YAML
    ) {
        $this->validateType($type);
        $this->router = $router;
        $this->swagger = new Builder($title, $description, $version, $tags);

        $this->readRoutes($exclude, $include);
        $this->addServers($servers);

        return $this->export($type);
    }

    /**
     * Read the routes.
     *
     * @param RouteFilter[] $exclude
     * @param RouteFilter[] $include
     *
     * @throws BindingResolutionException
     * @throws EndpointDoesntExistException
     */
    public function readRoutes(array $exclude, array $include = []): void
    {
        $this->routes($exclude, $include)
            ->each(function (Route $route) {
                $this->swagger->addPath(
                    $route->uri(),
                    $this->readRoute($route)
                );
            });
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
        return EndpointReader::readRoute($route, $route->methods())->all();
    }

    /**
     * Get the routes.
     *
     * @param RouteFilter[] $exclude
     * @param array         $include
     *
     * @return Collection
     */
    public function routes(array $exclude, array $include = []): Collection
    {
        $exclude = collect($exclude);
        $include = collect($include);

        return collect($this->router->getRoutes()->getRoutes())
            ->filter(function (Route $route) use ($exclude, $include) {
                $isExcluded = $exclude->some(fn (RouteFilter $filter) => $filter->matchesRoute($route));

                if ($isExcluded) {
                    return false;
                }

                return $include->isEmpty() || $include->some(fn (RouteFilter $filter) => $filter->matchesRoute($route));
            });
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
     * @param int $type
     *
     * @return string|array
     */
    protected function export(int $type)
    {
        $this->validateType($type);

        switch ($type) {
            case self::TYPE_JSON:
                return $this->swagger->toJson();
            case self::TYPE_YAML:
                return $this->swagger->toYaml();
            case self::TYPE_ARRAY:
            default:
                return $this->swagger->toArray();
        }
    }

    /**
     * Add the given servers.
     *
     * @param Server[] $servers
     *
     * @return bool
     */
    protected function addServers(array $servers): bool
    {
        return array_walk($servers, fn (Server $server) => $this->swagger->addServer($server));
    }
}