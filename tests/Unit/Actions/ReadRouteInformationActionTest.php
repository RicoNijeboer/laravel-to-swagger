<?php

namespace RicoNijeboer\Swagger\Tests\Unit\Actions;

use Closure;
use Illuminate\Http\Request;
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
