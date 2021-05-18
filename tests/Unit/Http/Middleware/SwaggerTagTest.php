<?php

namespace RicoNijeboer\Swagger\Tests\Unit\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RicoNijeboer\Swagger\Http\Middleware\SwaggerTag;
use RicoNijeboer\Swagger\Models\Batch;
use RicoNijeboer\Swagger\Models\Entry;
use RicoNijeboer\Swagger\Models\Tag;
use RicoNijeboer\Swagger\Tests\TestCase;

/**
 * Class SwaggerTagTest
 *
 * @package RicoNijeboer\Swagger\Tests\Unit\Middleware
 */
class SwaggerTagTest extends TestCase
{
    /**
     * @test
     */
    public function it_stores_the_tags_on_the_created_batch()
    {
        /** @var SwaggerTag $middleware */
        $middleware = resolve(SwaggerTag::class);
        $response = response()->noContent();
        $batch = Batch::factory()->state(['updated_at' => now()->subSeconds(120)])
            ->has(Entry::factory()->response($response))
            ->has(Entry::factory()->validation([]))
            ->has(Tag::factory(['tag' => 'keep']))
            ->create();

        $request = new Request();
        $request->setMethod($batch->route_method);
        $request->setRouteResolver(fn () => Route::name($batch->route_name)
            ->{strtolower($batch->route_method)}($batch->route_uri, fn () => $response)
            ->middleware($batch->route_middleware));

        // Simulate executing middleware while a user is waiting
        $middleware->handle($request, fn () => $response, 'add');

        // Simulate what happens after a response has been sent
        $middleware->terminate($request, $response, 'add');

        $this->assertDatabaseHas('swagger_tags', [
            'tag' => 'add',
        ]);
        $this->assertDatabaseHas('swagger_tags', [
            'tag' => 'keep',
        ]);

        $this->assertCount(2, $batch->tags()->get());
    }

    /**
     * @test
     */
    public function it_does_nothing_when_the_batch_does_not_exist()
    {
        /** @var SwaggerTag $middleware */
        $middleware = resolve(SwaggerTag::class);
        $response = response()->noContent();

        $request = new Request();
        $request->setMethod('GET');
        $request->setRouteResolver(fn () => Route::name('some.route')->get('uri', fn () => $response));

        $middleware->handle($request, fn () => $response, 'add');

        $this->assertDatabaseMissing('swagger_tags', [
            'tag' => 'add',
        ]);
    }
}
