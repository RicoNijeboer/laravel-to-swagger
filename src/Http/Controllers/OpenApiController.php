<?php

namespace RicoNijeboer\Swagger\Http\Controllers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\URL;
use RicoNijeboer\Swagger\Actions\BuildOpenApiConfigAction;
use RicoNijeboer\Swagger\Support\Formatter;
use RicoNijeboer\Swagger\Swagger;

/**
 * Class OpenApiController
 *
 * @package RicoNijeboer\Swagger\Http\Controllers
 */
class OpenApiController extends Controller
{
    /**
     * @param BuildOpenApiConfigAction $action
     *
     * @return Response
     * @throws BindingResolutionException
     */
    public function config(BuildOpenApiConfigAction $action): Response
    {
        $config = $action->build();

        return response()->make(
            Formatter::toYaml($config),
            200,
            [
                'content-type' => 'text/yaml',
            ]
        );
    }

    /**
     * @return Response
     */
    public function redoc(): Response
    {
        return response()->view('swagger::redoc', [
            'title'        => config('swagger.info.title'),
            'redocVersion' => config('swagger.redoc.version'),
            'specUrl'      => URL::temporarySignedRoute(
                Swagger::configRoute()->getName(),
                now()->addMinutes(15)
            ),
        ]);
    }

    /**
     * @return Response
     */
    public function swagger(): Response
    {
        return response()->view('swagger::swagger', [
            'title'   => config('swagger.info.title'),
            'specUrl' => URL::temporarySignedRoute(
                Swagger::configRoute()->getName(),
                now()->addMinutes(15)
            ),
        ]);
    }
}
