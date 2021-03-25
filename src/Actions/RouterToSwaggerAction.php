<?php

namespace Rico\Swagger\Actions;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Rico\Reader\Endpoints\EndpointReader;
use Rico\Reader\Exceptions\EndpointDoesntExistException;
use Rico\Swagger\Exceptions\UnsupportedSwaggerExportTypeException;
use Rico\Swagger\Support\Filter;
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
    private Router $router;
    private Builder $swagger;

    /**
     * Convert the router to the provided type.
     *
     * @param Router      $router
     * @param string|null $title
     * @param string|null $description
     * @param string|null $version
     * @param Server[]    $servers
     * @param Tag[]       $tags
     * @param Filter[]    $exclude
     * @param int         $type
     *
     * @return string
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
        int $type = self::TYPE_YAML
    ): string {
        $this->validateType($type);
        $this->router = $router;
        $this->swagger = Builder::new($title, $description, $version, $tags);

        $this->readRoutes($exclude);
        $this->addServers($servers);

        return $this->export($type);
    }

    /**
     * Read the routes.
     *
     * @param RouteFilter[] $exclude
     *
     * @throws BindingResolutionException
     * @throws EndpointDoesntExistException
     */
    public function readRoutes(array $exclude): void
    {
        $exclude = collect($exclude);

        $this->routes()
            ->filter(function (Route $route) use ($exclude) {
                return !$exclude->some(fn (RouteFilter $filter) => $filter->matchesRoute($route));
            })
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
        return EndpointReader::readRoute($route)
            ->all();
    }

    /**
     * Get the routes.
     *
     * @return Collection
     */
    public function routes(): Collection
    {
        return collect($this->router->getRoutes()->getRoutes());
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
     * @return string
     */
    protected function export(int $type): string
    {
        switch ($type) {
            case self::TYPE_JSON:
                return $this->swagger->toJson();
            case self::TYPE_YAML:
                return $this->swagger->toYaml();
        }

        return 'How did we get here?';
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