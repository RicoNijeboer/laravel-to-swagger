<?php

namespace RicoNijeboer\Swagger\Tests\Unit\Actions;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use RicoNijeboer\Swagger\Actions\ReadRouteInformationAction;
use RicoNijeboer\Swagger\Models\Batch;
use RicoNijeboer\Swagger\Models\Entry;
use RicoNijeboer\Swagger\Tests\TestCase;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Class ReadRouteInformationActionTest
 *
 * @package RicoNijeboer\Swagger\Tests\Unit\Actions
 */
class ReadRouteInformationActionTest extends TestCase
{
    /**
     * @test
     */
    public function it_stores_a_batch_when_called()
    {
        /** @var ReadRouteInformationAction $action */
        $action = resolve(ReadRouteInformationAction::class);
        $request = new Request();
        $request->server->set('REQUEST_METHOD', 'GET');

        $action->read($request, Route::get('index', fn () => response()->noContent())->bind($request), response()->noContent());

        $this->assertDatabaseCount('swagger_batches', 1);
        $this->assertDatabaseHas('swagger_batches', [
            'route_name'    => null,
            'route_uri'     => 'index',
            'route_method'  => 'GET',
            'response_code' => 204,
        ]);
    }

    /**
     * @test
     */
    public function it_adds_the_route_name_to_the_batch_when_available()
    {
        /** @var ReadRouteInformationAction $action */
        $action = resolve(ReadRouteInformationAction::class);
        $request = new Request();
        $request->server->set('REQUEST_METHOD', 'GET');
        $route = Route::get('index', fn () => response()->noContent())
            ->name('some.route.name')
            ->bind($request);

        $action->read($request, $route, response()->noContent());

        $this->assertDatabaseCount('swagger_batches', 1);
        $this->assertDatabaseHas('swagger_batches', [
            'route_name'   => 'some.route.name',
            'route_uri'    => 'index',
            'route_method' => 'GET',
        ]);
    }

    /**
     * @test
     */
    public function it_only_stores_a_batch_for_the_called_method()
    {
        /** @var ReadRouteInformationAction $action */
        $action = resolve(ReadRouteInformationAction::class);
        $request = new Request();
        $request->server->set('REQUEST_METHOD', 'HEAD');
        $route = Route::get('index', fn () => response()->noContent())->bind($request);

        $action->read($request, $route, response()->noContent());

        $this->assertDatabaseCount('swagger_batches', 1);
        $this->assertDatabaseHas('swagger_batches', [
            'route_name'   => null,
            'route_uri'    => 'index',
            'route_method' => 'HEAD',
        ]);
        $this->assertDatabaseMissing('swagger_batches', [
            'route_name'   => null,
            'route_uri'    => 'index',
            'route_method' => 'GET',
        ]);
    }

    /**
     * @test
     * @dataProvider responses
     */
    public function it_stores_an_entry_for_the_response(Closure $responseClosure)
    {
        /** @var SymfonyResponse $response */
        $response = $responseClosure();
        /** @var ReadRouteInformationAction $action */
        $action = resolve(ReadRouteInformationAction::class);
        $request = new Request();
        $request->server->set('REQUEST_METHOD', 'HEAD');
        $route = Route::get('index', $responseClosure)->bind($request);

        $action->read($request, $route, $response);

        $this->assertDatabaseHas('swagger_entries', [
            'type' => Entry::TYPE_RESPONSE,
        ]);
    }

    /**
     * @test
     */
    public function it_stores_an_entry_for_the_response_and_obfuscates_it()
    {
        $sentResponseArray = [
            'id'            => 1,
            'someDouble'    => 1337.14,
            'someFloat'     => 1337.418484,
            'name'          => 'foo',
            'password'      => 'please dont send passwords in your API responses, thanks',
            'someDateArray' => [
                'birthday'    => '04-02-1998',
                'datetime'    => '13-05-2021 08:51',
                'isoDate'     => now()->toIsoString(),
                'iso8601Date' => now()->toIso8601String(),
            ],
        ];
        $response = response()->json($sentResponseArray)->prepare(new Request());
        /** @var ReadRouteInformationAction $action */
        $action = resolve(ReadRouteInformationAction::class);
        $request = new Request();
        $request->server->set('REQUEST_METHOD', 'HEAD');
        $route = Route::get('index', fn () => $response)->bind($request);

        $action->read($request, $route, $response);

        /** @var Entry $responseEntry */
        $responseEntry = Entry::query()->where('type', '=', Entry::TYPE_RESPONSE)->firstOrFail();

        $storedResponseArray = json_decode($responseEntry->content['response'], true);

        foreach ($this->recursively($storedResponseArray) as [$item, $key]) {
            $this->assertNotEquals(
                Arr::get($sentResponseArray, $key),
                $item,
                "[{$key}] on the stored response equals the value that was sent in the response."
            );
        }
    }

    /**
     * @test
     */
    public function it_stores_an_entry_for_the_validation_rules()
    {
        /** @var ReadRouteInformationAction $action */
        $action = resolve(ReadRouteInformationAction::class);
        $request = new Request();
        $request->server->set('REQUEST_METHOD', 'HEAD');
        $route = Route::get('index', fn () => response()->noContent())->bind($request);
        $rules = ['email' => ['email', 'required']];

        $action->read($request, $route, response()->noContent(), $rules);

        $this->assertDatabaseHas('swagger_entries', [
            'type'    => Entry::TYPE_VALIDATION_RULES,
            'content' => json_encode($rules),
        ]);
    }

    /**
     * @test
     */
    public function it_stores_an_entry_for_the_route_parameters()
    {
        /** @var ReadRouteInformationAction $action */
        $action = resolve(ReadRouteInformationAction::class);
        $batch = Batch::factory()->create();
        $request = new Request();
        $request->server->set('REQUEST_URI', 'batches/' . $batch->id);
        $request->server->set('REQUEST_METHOD', 'GET');
        $route = Route::get('batches/{batch}', fn () => response()->noContent())->bind($request);
        $route->setParameter('batch', $batch);

        $action->read($request, $route, response()->noContent());

        $this->assertDatabaseHas('swagger_entries', [
            'type'    => Entry::TYPE_ROUTE_PARAMETERS,
            'content' => json_encode([
                'batch' => [
                    'class'    => Batch::class,
                    'required' => true,
                ],
            ]),
        ]);
    }

    /**
     * @test
     */
    public function it_stores_the_validated_get_parameters_in_the_route_parameters_entry()
    {
        /** @var ReadRouteInformationAction $action */
        $action = resolve(ReadRouteInformationAction::class);
        $request = new Request();
        $request->server->set('REQUEST_URI', 'batches');
        $request->server->set('REQUEST_METHOD', 'GET');
        $request->query->set('limit', 50);
        $request->query->set('with', []);
        $route = Route::get('batches', fn () => response()->noContent())->bind($request);

        $action->read($request, $route, response()->noContent(), [
            'limit' => ['required', 'numeric'],
            'with'  => ['nullable', 'array'],
        ]);

        $this->assertDatabaseHas('swagger_entries', [
            'type'    => Entry::TYPE_ROUTE_PARAMETERS,
            'content' => json_encode([
                'limit' => [
                    'class'    => null,
                    'type'     => 'number',
                    'required' => true,
                    'inQuery'  => true,
                    'rules'    => ['required', 'numeric'],
                ],
                'with'  => [
                    'class'    => null,
                    'type'     => 'array',
                    'required' => false,
                    'inQuery'  => true,
                    'rules'    => ['nullable', 'array'],
                ],
            ]),
        ]);
    }

    /**
     * @test
     */
    public function it_does_not_store_the_validated_get_parameters_in_the_validation_rules_entry()
    {
        /** @var ReadRouteInformationAction $action */
        $action = resolve(ReadRouteInformationAction::class);
        $request = new Request();
        $request->server->set('REQUEST_URI', 'batches');
        $request->server->set('REQUEST_METHOD', 'GET');
        $request->query->set('limit', 50);
        $request->query->set('with', []);
        $route = Route::get('batches', fn () => response()->noContent())->bind($request);

        $action->read($request, $route, response()->noContent(), [
            'limit' => ['required', 'numeric'],
            'with'  => ['nullable', 'array'],
        ]);

        /** @var Batch $batch */
        $batch = Batch::query()->latest()->firstOrFail();

        $this->assertArrayDoesntHaveKeys(['limit', 'with'], $batch->validationRulesEntry->content->jsonSerialize());
    }

    /**
     * @test
     */
    public function it_stores_an_entry_for_the_route_middlewares()
    {
        /** @var Router $router */
        $router = resolve(Router::class);
        /** @var ReadRouteInformationAction $action */
        $action = resolve(ReadRouteInformationAction::class);
        $batch = Batch::factory()->create();
        $request = new Request();
        $request->server->set('REQUEST_URI', 'batches/' . $batch->id);
        $request->server->set('REQUEST_METHOD', 'GET');
        $route = Route::middleware([
            'api',
            'scope:view.batches',
        ])->get('batches/{batch}', fn () => response()->noContent())->bind($request);
        $route->setParameter('batch', $batch);

        $router->middlewareGroup('api', [
            'throttle:api',
            'auth:api',
        ]);

        $router->aliasMiddleware('throttle', 'throttle-middleware');
        $router->aliasMiddleware('auth', 'auth-middleware');
        $router->aliasMiddleware('scope', 'scope-middleware');

        $action->read($request, $route, response()->noContent());

        $this->assertDatabaseHas('swagger_entries', [
            'type'    => Entry::TYPE_ROUTE_MIDDLEWARE,
            'content' => json_encode([
                'throttle-middleware:api',
                'auth-middleware:api',
                'scope-middleware:view.batches',
            ]),
        ]);
    }

    /**
     * @test
     */
    public function it_stores_an_entry_for_the_nullable_route_parameters()
    {
        /** @var ReadRouteInformationAction $action */
        $action = resolve(ReadRouteInformationAction::class);
        $batch = Batch::factory()->create();
        $request = new Request();
        $route = Route::get('batches/{batch?}', fn () => response()->noContent())->bind($request);
        $route->setParameter('batch', $batch);

        $action->read($request, $route, response()->noContent());

        $this->assertDatabaseHas('swagger_entries', [
            'type'    => Entry::TYPE_ROUTE_PARAMETERS,
            'content' => json_encode([
                'batch' => [
                    'class'    => Batch::class,
                    'required' => false,
                ],
            ]),
        ]);
    }

    /**
     * @test
     */
    public function when_the_route_has_a_separate_domain_it_is_stored_in_the_batch()
    {
        /** @var ReadRouteInformationAction $action */
        $action = resolve(ReadRouteInformationAction::class);
        $request = new Request();
        $route = Route::domain('other.example.com')->get('batches', fn () => response()->noContent())->bind($request);

        $action->read($request, $route, response()->noContent());

        /** @var Batch $batch */
        $batch = Batch::query()->latest()->firstOrFail();

        $this->assertNotNull($batch->route_domain);
    }

    /**
     * @test
     */
    public function it_stores_the_where_statements_when_a_route_has_them()
    {
        /** @var ReadRouteInformationAction $action */
        $action = resolve(ReadRouteInformationAction::class);
        $batch = Batch::factory()->create();
        $request = new Request();
        $route = Route::get('batches/{batch}', fn () => response()->noContent())->bind($request)
            ->where('batch', '[0-9]+');
        $route->setParameter('batch', $batch);

        $action->read($request, $route, response()->noContent());

        $this->assertDatabaseHas('swagger_entries', [
            'type'    => Entry::TYPE_ROUTE_WHERES,
            'content' => json_encode([
                'batch' => '[0-9]+',
            ]),
        ]);
    }

    /**
     * @return Closure[][]
     */
    public function responses(): array
    {
        return [
            'json'         => [
                fn () => response()
                    ->json([
                        [
                            'id'  => 1,
                            'foo' => 'bar',
                        ],
                    ])
                    ->prepare(new Request()),
            ],
            'noContent'    => [
                fn () => response()
                    ->noContent()
                    ->prepare(new Request()),
            ],
            'html or view' => [
                fn () => response()
                    ->make('<h1>Hello world!</h1>')
                    ->prepare(new Request()),
            ],
        ];
    }
}
