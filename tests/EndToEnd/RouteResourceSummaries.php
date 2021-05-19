<?php

namespace RicoNijeboer\Swagger\Tests\EndToEnd;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use RicoNijeboer\Swagger\Actions\BuildOpenApiConfigAction;
use RicoNijeboer\Swagger\Actions\ReadRouteInformationAction;
use RicoNijeboer\Swagger\Models\Batch;
use RicoNijeboer\Swagger\Tests\App\Http\Controllers\TestController;
use RicoNijeboer\Swagger\Tests\TestCase;

/**
 * @see     https://github.com/RicoNijeboer/laravel-to-swagger/issues/19
 */
class RouteResourceSummaries extends TestCase
{
    /**
     * @test
     */
    public function it_gives_update_routes_of_resources_a_summary()
    {
        /** @var ReadRouteInformationAction $readAction */
        $readAction = resolve(ReadRouteInformationAction::class);
        /** @var BuildOpenApiConfigAction $buildAction */
        $buildAction = resolve(BuildOpenApiConfigAction::class);
        $route = RouteFacade::middleware('swagger_reader')
                     ->resource('batches', TestController::class)
                     ->only(['update'])
                     ->register()
                     ->getRoutes()[0];
        $response = response()->noContent();

        // Read the route with all possible methods.
        foreach ($route->methods() as $method) {
            $request = $this->createRequest($route, strtoupper($method));

            $readAction->read($request, $route, $response);

            Batch::forRequestAndResponse($request, $response)
                ->with([
                    'validationRulesEntry',
                    'responseEntry',
                    'parameterEntry',
                    'parameterWheresEntry',
                ])
                ->firstOrFail();
        }

        $result = $buildAction->build();

        $this->assertArrayHasKeys(
            [
                'paths./batches/{batch}.put.summary'   => 'batches.update',
                'paths./batches/{batch}.patch.summary' => 'batches.update',
            ],
            $result
        );
    }

    protected function createRequest(Route $route, string $method): Request
    {
        $tmpBatch = Batch::factory()->create();

        $request = new Request();
        $request->server->set('REQUEST_METHOD', $method);
        $route->bind($request);
        $route->setParameter('batch', $tmpBatch);
        $request->setRouteResolver(fn () => $route);

        $tmpBatch->delete(); // Cleanup

        return $request;
    }
}
