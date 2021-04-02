<?php

namespace Rico\Swagger\Routing;

use Illuminate\Routing\Router;

/**
 * Class RouteRegistrar
 *
 * @package Rico\Swagger\Routing
 */
class RouteRegistrar
{
    private Router $router;

    /**
     * RouteRegistrar constructor.
     *
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @return $this
     */
    public function forSwaggerConfig(): self
    {
        $this->router->name('config')
            ->get('/config', 'SwaggerController@config');

        return $this;
    }
}