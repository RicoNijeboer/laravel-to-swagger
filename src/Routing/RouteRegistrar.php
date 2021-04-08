<?php

namespace Rico\Swagger\Routing;

use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\URL;
use Rico\Swagger\Swagger;

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
            ->get('config', 'SwaggerController@config');

        return $this;
    }

    /**
     * @param string $uri
     *
     * @return $this
     */
    public function forDocumentation(string $uri = '/documentation'): self
    {
        $configRoute = $this->router->name('documentation.config')
            ->middleware(ValidateSignature::class)
            ->get($uri . '/config', 'SwaggerController@config');

        Swagger::configUri(URL::signedRoute($configRoute->getName()));

        $this->router->name('documentation')
            ->get($uri, 'SwaggerController@redoc');

        return $this;
    }
}