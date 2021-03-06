<?php

namespace RicoNijeboer\Swagger\Http\Routing;

use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Routing\Router;
use RicoNijeboer\Swagger\Http\Controllers\OpenApiController;
use RicoNijeboer\Swagger\Swagger;

/**
 * Class RouteRegistrar
 *
 * @package RicoNijeboer\Swagger\Http\Routing
 */
class RouteRegistrar
{
    private Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function forDocumentation(string $uri = 'docs', bool $redoc = true)
    {
        $openApiConfigRoute = $this->router->name('documentation.config')
            ->middleware(ValidateSignature::class)
            ->get($uri . '/config', [OpenApiController::class, 'config']);

        Swagger::configRoute($openApiConfigRoute);

        if ($redoc) {
            $this->router->name('documentation.redoc')
                ->get($uri, [OpenApiController::class, 'redoc']);
        } else {
            $this->router->name('documentation.swagger')
                ->get($uri, [OpenApiController::class, 'swagger']);
        }
    }
}
