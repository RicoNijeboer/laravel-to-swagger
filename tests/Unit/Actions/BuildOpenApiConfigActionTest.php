<?php

namespace RicoNijeboer\Swagger\Tests\Unit\Actions;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RicoNijeboer\Swagger\Actions\BuildOpenApiConfigAction;
use RicoNijeboer\Swagger\Models\Batch;
use RicoNijeboer\Swagger\Models\Entry;
use RicoNijeboer\Swagger\Models\Tag;
use RicoNijeboer\Swagger\Tests\TestCase;

class BuildOpenApiConfigActionTest extends TestCase
{
    /**
     * @test
     */
    public function it_loads_all_basic_open_api_information()
    {
        /** @var BuildOpenApiConfigAction $action */
        $action = resolve(BuildOpenApiConfigAction::class);

        config()->set('swagger.info.title', 'Some title');
        config()->set('swagger.info.description', 'The description of your API');
        config()->set('swagger.info.version', 'v0.0.1');

        config()->set('swagger.servers', [
            [
                'url'         => 'https://api.example.com',
                'description' => null,
            ],
        ]);

        $result = $action->build();

        $this->assertArrayHasKeys(
            [
                'info.title'       => 'Some title',
                'info.description' => 'The description of your API',
                'info.version'     => 'v0.0.1',
                'openapi'          => '3.0.0',
                'servers.0.url'    => 'https://api.example.com',
                'paths',
            ],
            $result
        );
        $this->assertIsArray($result['paths']);
        $this->assertCount(0, $result['paths']);
    }

    /**
     * @test
     */
    public function it_contains_all_paths()
    {
        $batches = Batch::factory()
            ->count(2)
            ->state(new Sequence(
                ['route_uri' => 'products', 'route_method' => 'GET', 'route_name' => 'some.route.name', 'response_code' => 200],
                ['route_uri' => 'categories', 'route_method' => 'GET', 'route_name' => null, 'response_code' => 200]
            ))
            ->has(Entry::factory()->validation())
            ->has(Entry::factory()->response())
            ->has(Entry::factory()->parameters(['batch' => ['class' => Batch::class, 'required' => true]]))
            ->create()
            ->load([
                'validationRulesEntry',
                'responseEntry',
            ]);

        /** @var Batch $firstBatch */
        $firstBatch = $batches->first();
        $firstBatch->tags()->save(Tag::factory()->create());

        /** @var BuildOpenApiConfigAction $action */
        $action = resolve(BuildOpenApiConfigAction::class);

        $result = $action->build();

        $batches->each(
            function (Batch $batch, int $index) use ($result) {
                $uri = Str::start($batch->route_uri, '/');
                $method = strtolower($batch->route_method);

                $this->assertArrayHasKeys(
                    [
                        "paths.{$uri}.{$method}.summary" => $batch->route_name ?? $batch->route_uri,
                    ],
                    $result
                );
            }
        );
    }

    /**
     * @test
     */
    public function it_merges_the_response_codes_when_the_uri_and_method_are_the_same()
    {
        $batches = Batch::factory()
            ->count(2)
            ->state(new Sequence(
                ['route_uri' => 'products', 'route_method' => 'GET', 'route_name' => 'some.route.name', 'response_code' => 200],
                ['route_uri' => 'products', 'route_method' => 'GET', 'route_name' => 'some.route.name', 'response_code' => 422],
            ))
            ->has(Entry::factory()->validation())
            ->has(Entry::factory()->response())
            ->has(Entry::factory()->parameters(['batch' => ['class' => Batch::class, 'required' => true]]))
            ->create()
            ->load([
                'validationRulesEntry',
                'responseEntry',
            ]);

        /** @var BuildOpenApiConfigAction $action */
        $action = resolve(BuildOpenApiConfigAction::class);

        $result = $action->build();

        $batches->each(
            function (Batch $batch, int $index) use ($result) {
                $uri = Str::start($batch->route_uri, '/');
                $method = strtolower($batch->route_method);

                $this->assertArrayHasKeys(
                    [
                        "paths.{$uri}.{$method}.summary" => $batch->route_name ?? $batch->route_uri,
                        "paths.{$uri}.{$method}.responses.{$batch->response_code}",
                    ],
                    $result
                );
            }
        );
    }

    /**
     * @test
     */
    public function it_contains_the_request_body_based_on_the_validation_entry()
    {
        Batch::factory()
            ->state(new Sequence(
                ['route_uri' => 'products', 'route_method' => 'POST', 'route_name' => 'products.create', 'response_code' => 200],
            ))
            ->has(Entry::factory()->validation([
                'email'         => ['required', 'email'],
                'country.code'  => ['required', 'min:2', 'max:2'],
                'products.*.id' => ['required', 'numeric'],
                'items.*'       => ['required'],
            ]))
            ->has(Entry::factory()->response())
            ->has(Entry::factory()->parameters(['batch' => ['class' => Batch::class, 'required' => true]]))
            ->create()
            ->load([
                'validationRulesEntry',
                'responseEntry',
            ]);

        /** @var BuildOpenApiConfigAction $action */
        $action = resolve(BuildOpenApiConfigAction::class);

        $result = $action->build();

        $this->assertArrayHasKeys(
            [
                'requestBody.content.application/json.schema.type'                                         => 'object',
                'requestBody.content.application/json.schema.properties.email.type'                        => 'string',
                'requestBody.content.application/json.schema.properties.email.format'                      => 'email',
                'requestBody.content.application/json.schema.properties.country.type'                      => 'object',
                'requestBody.content.application/json.schema.properties.country.properties.code.type'      => 'string',
                'requestBody.content.application/json.schema.properties.country.properties.code.minimum'   => 2,
                'requestBody.content.application/json.schema.properties.country.properties.code.maximum'   => 2,
                'requestBody.content.application/json.schema.properties.products.type'                     => 'array',
                'requestBody.content.application/json.schema.properties.products.items.type'               => 'object',
                'requestBody.content.application/json.schema.properties.products.items.properties.id.type' => 'number',
                'requestBody.content.application/json.schema.properties.items.type'                        => 'array',
                'requestBody.content.application/json.schema.properties.items.items.type'                  => 'string',
            ],
            Arr::get($result, 'paths./products.post')
        );
    }

    /**
     * @test
     */
    public function it_describes_html_responses()
    {
        /** @var BuildOpenApiConfigAction $action */
        $action = resolve(BuildOpenApiConfigAction::class);
        $response = response()->make('<h1>Hello world!</h1>')->prepare(new Request());

        Batch::factory(['route_uri' => 'products', 'route_method' => 'GET', 'response_code' => 200])
            ->has(Entry::factory()->validation())
            ->has(Entry::factory()->response($response))
            ->has(Entry::factory()->parameters(['batch' => ['class' => Batch::class, 'required' => true]]))
            ->create();

        $result = $action->build();

        $this->assertArrayHasKeys(
            [
                'paths./products.get.responses.200.content.text/html.schema.type' => 'string',
            ],
            $result
        );
    }

    /**
     * @test
     */
    public function it_does_not_describe_no_content_responses()
    {
        /** @var BuildOpenApiConfigAction $action */
        $action = resolve(BuildOpenApiConfigAction::class);
        $response = response()->noContent()->prepare(new Request());

        Batch::factory(['route_uri' => 'products', 'route_method' => 'GET', 'response_code' => 204])
            ->has(Entry::factory()->validation())
            ->has(Entry::factory()->response($response))
            ->has(Entry::factory()->parameters(['batch' => ['class' => Batch::class, 'required' => true]]))
            ->create();

        $result = $action->build();

        $this->assertArrayDoesntHaveKeys(
            [
                'paths./products.get.responses.204.content',
            ],
            $result
        );
    }

    /**
     * @test
     */
    public function it_describes_json_responses()
    {
        /** @var BuildOpenApiConfigAction $action */
        $action = resolve(BuildOpenApiConfigAction::class);
        $response = response()->json([
            'id'       => 23,
            'name'     => 'Rico Nijeboer',
            'birthday' => '1998-02-04',
            'roles'    => ['developer'],
            'foo'      => ['bar' => 'baz'],
        ])->prepare(new Request());

        Batch::factory(['route_uri' => 'products', 'route_method' => 'GET', 'response_code' => 200])
            ->has(Entry::factory()->validation())
            ->has(Entry::factory()->response($response))
            ->has(Entry::factory()->parameters(['batch' => ['class' => Batch::class, 'required' => true]]))
            ->create();

        $result = $action->build();

        $this->assertArrayHasKeys(
            [
                'paths./products.get.responses.200.content.application/json.schema.type'                               => 'object',
                'paths./products.get.responses.200.content.application/json.schema.properties.id.type'                 => 'integer',
                'paths./products.get.responses.200.content.application/json.schema.properties.name.type'               => 'string',
                'paths./products.get.responses.200.content.application/json.schema.properties.birthday.type'           => 'string',
                'paths./products.get.responses.200.content.application/json.schema.properties.roles.type'              => 'array',
                'paths./products.get.responses.200.content.application/json.schema.properties.roles.items.type'        => 'string',
                'paths./products.get.responses.200.content.application/json.schema.properties.foo.type'                => 'object',
                'paths./products.get.responses.200.content.application/json.schema.properties.foo.properties.bar.type' => 'string',
            ],
            $result
        );
    }

    /**
     * @test
     */
    public function it_adds_the_tags_to_the_paths()
    {
        $batch = Batch::factory(['route_uri' => 'products', 'route_method' => 'GET', 'response_code' => 204])
            ->has(Entry::factory()->validation())
            ->has(Entry::factory()->response())
            ->has(Entry::factory()->parameters(['batch' => ['class' => Batch::class, 'required' => true]]))
            ->has(Tag::factory()->count(3))
            ->create();

        /** @var BuildOpenApiConfigAction $action */
        $action = resolve(BuildOpenApiConfigAction::class);

        $result = $action->build();

        $path = Arr::get($result, 'paths./products.get');

        $this->assertArrayHasKey('tags', $path);
        $this->assertCount(3, $path['tags']);

        $batch->tags->each(fn (Tag $tag) => $this->asserttrue(in_array($tag->tag, $path['tags'])));
    }

    /**
     * @test
     */
    public function it_adds_the_parameters_to_the_paths()
    {
        Batch::factory(['route_uri' => 'products', 'route_method' => 'GET', 'response_code' => 204])
            ->has(Entry::factory()->validation())
            ->has(Entry::factory()->response())
            ->has(Entry::factory()->parameters(['batch' => ['class' => Batch::class, 'required' => true]]))
            ->create();

        /** @var BuildOpenApiConfigAction $action */
        $action = resolve(BuildOpenApiConfigAction::class);

        $result = $action->build();

        $this->assertArrayHasKeys(
            [
                'paths./products.get.parameters.0.in'          => 'path',
                'paths./products.get.parameters.0.name'        => 'batch',
                'paths./products.get.parameters.0.schema.type' => 'string',
                'paths./products.get.parameters.0.required'    => true,
                'paths./products.get.parameters.0.description' => 'Batch',
            ],
            $result
        );
    }

    /**
     * @test
     */
    public function it_displays_a_separate_server_on_the_path_when_the_batch_has_a_route_domain()
    {
        Batch::factory(['route_uri' => 'products', 'route_method' => 'GET', 'response_code' => 204, 'route_domain' => 'echo.example.com'])
            ->has(Entry::factory()->validation())
            ->has(Entry::factory()->response())
            ->has(Entry::factory()->parameters())
            ->create();

        /** @var BuildOpenApiConfigAction $action */
        $action = resolve(BuildOpenApiConfigAction::class);

        $result = $action->build();

        $this->assertArrayHasKeys(
            [
                'paths./products.get.servers.0.url' => 'echo.example.com',
            ],
            $result
        );
    }
}
