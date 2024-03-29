<?php

namespace RicoNijeboer\Swagger\Tests\Unit\Actions;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Laravel\Passport\Http\Middleware\CheckForAnyScope;
use Laravel\Passport\Http\Middleware\CheckScopes;
use Laravel\Passport\Passport;
use RicoNijeboer\Swagger\Actions\BuildOpenApiConfigAction;
use RicoNijeboer\Swagger\Exceptions\MalformedServersException;
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
    public function it_contains_a_format_when_a_validated_field_has_a_regex_rule()
    {
        Batch::factory()
            ->state(new Sequence(
                ['route_uri' => 'products', 'route_method' => 'POST', 'route_name' => 'products.create', 'response_code' => 200],
            ))
            ->has(Entry::factory()->validation([
                'field' => ['required', 'string', 'regex:/[0-9]_[a-z]/'],
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
                'requestBody.content.application/json.schema.type'                    => 'object',
                'requestBody.content.application/json.schema.properties.field.type'   => 'string',
                'requestBody.content.application/json.schema.properties.field.format' => '[0-9]_[a-z]',
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
    public function it_adds_the_query_parameters_to_the_paths()
    {
        Batch::factory(['route_uri' => 'products', 'route_method' => 'GET', 'response_code' => 204])
            ->has(Entry::factory()->validation())
            ->has(Entry::factory()->response())
            ->has(Entry::factory()->parameters([
                'limit'  => [
                    'class'    => null,
                    'type'     => 'number',
                    'required' => true,
                    'inQuery'  => true,
                    'rules'    => ['required', 'numeric'],
                ],
                'with'   => [
                    'class'    => null,
                    'type'     => 'array',
                    'required' => false,
                    'inQuery'  => true,
                    'rules'    => ['nullable', 'array'],
                ],
                'search' => [
                    'class'    => null,
                    'type'     => 'string',
                    'required' => false,
                    'inQuery'  => true,
                    'rules'    => ['nullable', 'string', 'regex:/\d+\_[a-z]+/'],
                ],
            ]))
            ->create();

        /** @var BuildOpenApiConfigAction $action */
        $action = resolve(BuildOpenApiConfigAction::class);

        $result = $action->build();

        $this->assertArrayHasKeys(
            [
                'paths./products.get.parameters.0.in'            => 'query',
                'paths./products.get.parameters.0.name'          => 'limit',
                'paths./products.get.parameters.0.schema.type'   => 'number',
                'paths./products.get.parameters.0.required'      => true,
                'paths./products.get.parameters.1.in'            => 'query',
                'paths./products.get.parameters.1.name'          => 'with',
                'paths./products.get.parameters.1.schema.type'   => 'array',
                'paths./products.get.parameters.1.required'      => false,
                'paths./products.get.parameters.2.in'            => 'query',
                'paths./products.get.parameters.2.name'          => 'search',
                'paths./products.get.parameters.2.required'      => false,
                'paths./products.get.parameters.2.schema.type'   => 'string',
                'paths./products.get.parameters.2.schema.format' => '\d+\_[a-z]+',
            ],
            $result
        );
    }

    /**
     * @test
     */
    public function it_adds_all_non_grouped_tags_to_a_default_group_which_is_named_by_the_config()
    {
        $tag1 = Tag::factory()->create(['tag' => 'Test']);
        $tag2 = Tag::factory()->create(['tag' => 'Users']);
        $batch = Batch::factory(['route_uri' => 'products', 'route_method' => 'GET', 'response_code' => 204])
            ->has(Entry::factory()->validation())
            ->has(Entry::factory()->response())
            ->has(Entry::factory()->parameters([
                'limit'  => [
                    'class'    => null,
                    'type'     => 'number',
                    'required' => true,
                    'inQuery'  => true,
                    'rules'    => ['required', 'numeric'],
                ],
                'with'   => [
                    'class'    => null,
                    'type'     => 'array',
                    'required' => false,
                    'inQuery'  => true,
                    'rules'    => ['nullable', 'array'],
                ],
                'search' => [
                    'class'    => null,
                    'type'     => 'string',
                    'required' => false,
                    'inQuery'  => true,
                    'rules'    => ['nullable', 'string', 'regex:/\d+\_[a-z]+/'],
                ],
            ]))
            ->create();

        $batch->tags()->attach($tag1->id);
        $batch->save();
        $batch2 = Batch::factory(['route_uri' => 'products', 'route_method' => 'POST', 'response_code' => 200])
            ->has(Entry::factory()->validation())
            ->has(Entry::factory()->response())
            ->has(Entry::factory()->parameters([
                'limit'  => [
                    'class'    => null,
                    'type'     => 'number',
                    'required' => true,
                    'inQuery'  => true,
                    'rules'    => ['required', 'numeric'],
                ],
                'with'   => [
                    'class'    => null,
                    'type'     => 'array',
                    'required' => false,
                    'inQuery'  => true,
                    'rules'    => ['nullable', 'array'],
                ],
                'search' => [
                    'class'    => null,
                    'type'     => 'string',
                    'required' => false,
                    'inQuery'  => true,
                    'rules'    => ['nullable', 'string', 'regex:/\d+\_[a-z]+/'],
                ],
            ]))
            ->create();

        $batch2->tags()->attach($tag2->id);
        $batch2->save();

        Config::set('swagger.redoc.default-group', 'default group');
        Config::set('swagger.redoc.tag-groups', [
            [
                'name' => 'user mana',
                'tags' => ['Users'],
            ],
        ]);

        /** @var BuildOpenApiConfigAction $action */
        $action = resolve(BuildOpenApiConfigAction::class);

        $result = $action->build();

        $this->assertArrayHasKeys(
            [
                'x-tagGroups.0.name' => 'default group',
                'x-tagGroups.0.tags.0' => 'Test',
                'x-tagGroups.1.name' => 'user mana',
                'x-tagGroups.1.tags.0' => 'Users',
            ],
            $result
        );
    }

    /**
     * @test
     */
    public function it_does_not_fail_when_the_batch_does_not_have_a_parameter_wheres_entry()
    {
        Batch::factory(['route_uri' => 'products', 'route_method' => 'GET', 'response_code' => 204])
            ->has(Entry::factory()->validation())
            ->has(Entry::factory()->response())
            ->has(Entry::factory()->parameters(['batch' => ['class' => Batch::class, 'required' => true]]))
            ->create();

        /** @var BuildOpenApiConfigAction $action */
        $action = resolve(BuildOpenApiConfigAction::class);

        $result = $action->build();

        $this->assertIsArray($result);
    }

    /**
     * @test
     */
    public function it_adds_a_format_rule_to_parameters_that_have_parameter_wheres()
    {
        Batch::factory(['route_uri' => 'products', 'route_method' => 'GET', 'response_code' => 204])
            ->has(Entry::factory()->validation())
            ->has(Entry::factory()->response())
            ->has(Entry::factory()->parameters(['batch' => ['class' => Batch::class, 'required' => true]]))
            ->has(Entry::factory()->wheres(['batch' => '[0-9]+']))
            ->create();

        /** @var BuildOpenApiConfigAction $action */
        $action = resolve(BuildOpenApiConfigAction::class);

        $result = $action->build();

        $this->assertArrayHasKeys(
            [
                'paths./products.get.parameters.0.description'   => 'Batch',
                'paths./products.get.parameters.0.schema.format' => '[0-9]+',
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

    /**
     * @test
     */
    public function it_does_not_return_an_array_of_variables_on_servers_when_the_server_does_not_contain_variables()
    {
        /** @var BuildOpenApiConfigAction $action */
        $action = resolve(BuildOpenApiConfigAction::class);
        config()->set('swagger.servers', [
            [
                'url' => 'https://api.example.com',
            ],
        ]);

        $result = $action->build();

        $this->assertArrayDoesntHaveKeys(
            ['servers.0.parameters'],
            $result
        );
    }

    /**
     * @test
     * @throws MalformedServersException
     */
    public function it_contains_an_array_of_variables_on_servers_when_the_server_has_variables()
    {
        /** @var BuildOpenApiConfigAction $action */
        $action = resolve(BuildOpenApiConfigAction::class);
        config()->set('swagger.servers', [
            [
                'url'       => 'https://{customerId}.saas-app.com:{port}/v2',
                'variables' => [
                    'customerId' => [
                        'default'     => 'demo',
                        'description' => 'Customer ID assigned by the service provider',
                    ],
                    'port'       => [
                        'enum'    => [
                            '443',
                            '8443',
                        ],
                        'default' => '443',
                    ],
                ],
            ],
        ]);

        $result = $action->build();

        $this->assertArrayHasKeys(
            [
                'servers.0.url'                              => 'https://{customerId}.saas-app.com:{port}/v2',
                'servers.0.variables.customerId.default'     => 'demo',
                'servers.0.variables.customerId.description' => 'Customer ID assigned by the service provider',
                'servers.0.variables.port.enum.0'            => '443',
                'servers.0.variables.port.enum.1'            => '8443',
                'servers.0.variables.port.default'           => '443',
            ],
            $result
        );
    }

    /**
     * @test
     * @dataProvider malformedServers
     */
    public function it_throws_an_exception_when_the_server_config_is_malformed($server)
    {
        $this->expectException(MalformedServersException::class);

        /** @var BuildOpenApiConfigAction $action */
        $action = resolve(BuildOpenApiConfigAction::class);
        config()->set('swagger.servers', [$server]);

        $action->build();
    }

    /**
     * @test
     */
    public function it_adds_the_security_schemes_to_the_paths()
    {
        config()->set('auth.guards', [
            'api' => [
                'driver'   => 'passport',
                'provider' => 'users',
            ],
        ]);
        Batch::factory(['route_uri' => 'products', 'route_method' => 'GET', 'response_code' => 204])
            ->has(Entry::factory()->validation())
            ->has(Entry::factory()->response())
            ->has(Entry::factory()->middleware([
                Authenticate::class . ':api',
            ]))
            ->has(Entry::factory()->parameters(['batch' => ['class' => Batch::class, 'required' => true]]))
            ->create();

        /** @var BuildOpenApiConfigAction $action */
        $action = resolve(BuildOpenApiConfigAction::class);

        $result = $action->build();

        $this->assertArrayHasKeys(
            [
                'paths./products.get.security.0' => 'api',
            ],
            $result
        );
    }

    /**
     * @test
     * @dataProvider checkScopeMiddlewareClasses
     */
    public function it_adds_the_security_schemes_to_the_paths_and_adds_the_scopes_needed_too(string $checkScopeClass)
    {
        config()->set('auth.guards', [
            'api' => [
                'driver'   => 'passport',
                'provider' => 'users',
            ],
        ]);

        Passport::tokensCan(['check-status' => 'Lorem ipsum.']);

        Batch::factory(['route_uri' => 'products', 'route_method' => 'GET', 'response_code' => 204])
            ->has(Entry::factory()->validation())
            ->has(Entry::factory()->response())
            ->has(Entry::factory()->middleware([
                Authenticate::class . ':api',
                $checkScopeClass . ':check-status',
            ]))
            ->has(Entry::factory()->parameters(['batch' => ['class' => Batch::class, 'required' => true]]))
            ->create();

        /** @var BuildOpenApiConfigAction $action */
        $action = resolve(BuildOpenApiConfigAction::class);

        $result = $action->build();

        $this->assertArrayHasKeys(
            [
                'paths./products.get.security.0.api.0' => 'check-status',
            ],
            $result
        );
    }

    /**
     * @test
     */
    public function it_adds_the_security_schemes_to_the_paths_and_adds_the_scopes_for_both_scope_middlewares_if_they_are_both_added()
    {
        $checkScopeMiddlewareClasses = array_values(Arr::flatten($this->checkScopeMiddlewareClasses()));

        config()->set('auth.guards', [
            'api' => [
                'driver'   => 'passport',
                'provider' => 'users',
            ],
        ]);
        Passport::tokensCan($scopes = ['check-status' => 'Lorem ipsum.', 'lorem' => 'ipsum']);

        Batch::factory(['route_uri' => 'products', 'route_method' => 'GET', 'response_code' => 204])
            ->has(Entry::factory()->validation())
            ->has(Entry::factory()->response())
            ->has(Entry::factory()->middleware(
                array_merge(
                    [
                        Authenticate::class . ':api',
                    ],
                    array_map(
                        fn (string $middlewareClass, int $index) => $middlewareClass . ':' . array_keys($scopes)[$index],
                        $checkScopeMiddlewareClasses,
                        array_keys($checkScopeMiddlewareClasses)
                    )
                )
            ))
            ->has(Entry::factory()->parameters(['batch' => ['class' => Batch::class, 'required' => true]]))
            ->create();

        /** @var BuildOpenApiConfigAction $action */
        $action = resolve(BuildOpenApiConfigAction::class);

        $result = $action->build();

        $this->assertArrayHasKeys(
            [
                'paths./products.get.security.0.api.0' => 'check-status',
                'paths./products.get.security.0.api.1' => 'lorem',
            ],
            $result
        );
    }

    /**
     * @return array
     */
    public function malformedServers(): array
    {
        return [
            'no url'                           => [
                [
                    'description' => 'This server has no url.',
                ],
            ],
            'variables as string array'        => [
                [
                    'url'       => 'https://{customerId}.saas-app.com:{port}/v2',
                    'variables' => [
                        'customerId',
                        'port',
                    ],
                ],
            ],
            'variables with strings as values' => [
                [
                    'url'       => 'https://{customerId}.saas-app.com:{port}/v2',
                    'variables' => [
                        'customerId' => 'demo',
                        'port'       => '443',
                    ],
                ],
            ],
        ];
    }

    public function checkScopeMiddlewareClasses(): array
    {
        return [
            'CheckScopes'      => [CheckScopes::class],
            'CheckForAnyScope' => [CheckForAnyScope::class],
        ];
    }
}
