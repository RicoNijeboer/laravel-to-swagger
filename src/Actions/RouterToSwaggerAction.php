<?php

namespace Rico\Swagger\Actions;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Rico\Reader\Endpoints\EndpointReader;
use Rico\Reader\Exceptions\EndpointDoesntExistException;
use Rico\Swagger\Exceptions\UnsupportedSwaggerExportTypeException;
use Rico\Swagger\Swagger\Builder;
use Rico\Swagger\Swagger\Server;
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
     * @param int         $type
     *
     * @return string
     * @throws BindingResolutionException
     * @throws EndpointDoesntExistException
     * @throws UnsupportedSwaggerExportTypeException
     */
    public function convert(Router $router, ?string $title = null, ?string $description = null, ?string $version = null, array $servers = [], int $type = self::TYPE_YAML): string
    {
        $this->validateType($type);
        $this->router = $router;
        $this->swagger = new Builder($title, $description, $version);

        $this->readRoutes();
        $this->addServers($servers);

        return $this->export($type);
    }

    /**
     * Read the routes.
     *
     * @throws BindingResolutionException
     * @throws EndpointDoesntExistException
     */
    public function readRoutes(): void
    {
        $this->routes()
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
        switch ($type)
        {
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
        switch ($type)
        {
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
        return array_walk($servers, fn(Server $server) => $this->swagger->addServer($server));
    }
}