<?php

namespace Rico\Swagger\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Routing\Router;
use ReflectionException;
use Rico\Swagger\Actions\RouterToSwaggerAction;
use Rico\Swagger\Exceptions\UnsupportedSwaggerExportTypeException;
use Rico\Swagger\Swagger;

/**
 * Class SwaggerController
 *
 * @package Rico\Swagger\Http\Controllers
 */
class SwaggerController extends Controller
{
    /**
     * Generates a Swagger config.
     *
     * @param RouterToSwaggerAction $swagger
     * @param Router                $router
     *
     * @return Application|ResponseFactory|Response
     * @throws ReflectionException
     * @throws UnsupportedSwaggerExportTypeException
     */
    public function config(RouterToSwaggerAction $swagger, Router $router)
    {
        $oauthConfig = Swagger::oauthConfig($router);
        $swaggerConfig = $swagger->convert(
            $router,
            config('swagger.title'),
            config('swagger.description'),
            config('swagger.version'),
            Swagger::servers(),
            Swagger::tags(),
            Swagger::include(),
            Swagger::exclude(),
            RouterToSwaggerAction::TYPE_YAML,
            $oauthConfig
        );

        return response(
            $swaggerConfig,
            200,
            [
                'content-type' => 'text/yaml',
            ]
        );
    }

    public function redoc()
    {
        return response()->view('swagger::redoc', [
            'title'        => config('swagger.title'),
            'specUrl'      => Swagger::configUri(),
            'redocVersion' => config('swagger.redoc.version'),
        ]);
    }
}