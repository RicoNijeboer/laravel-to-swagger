<?php

namespace RicoNijeboer\Swagger\Tests\Unit\Data;

use Illuminate\Http\Request;
use RicoNijeboer\Swagger\Data\PathData;
use RicoNijeboer\Swagger\Models\Batch;
use RicoNijeboer\Swagger\Models\Entry;
use RicoNijeboer\Swagger\Tests\TestCase;

/**
 * Class PathDataTest
 *
 * @package RicoNijeboer\Swagger\Tests\Unit\Data
 */
class PathDataTest extends TestCase
{
    /**
     * @test
     */
    public function it_calculates_all_properties()
    {
        $batch = Batch::factory()
            ->has(Entry::factory()->response())
            ->has(Entry::factory([
                'type'    => Entry::TYPE_VALIDATION_RULES,
                'content' => [
                    'email'                    => ['required', 'email'],
                    'name'                     => ['nullable', 'string'],
                    'birthday'                 => ['required', 'date_format:d-m-Y'],
                    'country.code'             => ['required'],
                    'items'                    => ['array', 'min:2', 'max:10'],
                    'items.0.code'             => ['required', 'min:2', 'max:2'],
                    'items.1.code'             => ['required', 'min:2', 'max:2'],
                    'products.*'               => ['required'],
                    'order.id'                 => ['required'],
                    'order.address.address'    => ['required'],
                    'order.address.postalCode' => ['required'],
                ],
            ]))
            ->has(Entry::factory()->parameters())
            ->create();

        $data = new PathData($batch);

        $properties = $this->property($data, 'properties')->getValue($data);

        $this->assertArrayHasKeys(
            [
                'email.type'                                          => 'string',
                'email.format'                                        => 'email',
                'name.type'                                           => 'string',
                'name.nullable'                                       => true,
                'birthday.type'                                       => 'string',
                'country.type'                                        => 'object',
                'country.properties.code.type'                        => 'string',
                'items.type'                                          => 'array',
                'items.minItems'                                      => 2,
                'items.maxItems'                                      => 10,
                'items.items.type'                                    => 'object',
                'items.items.properties.code.type'                    => 'string',
                'items.items.properties.code.minimum'                 => 2,
                'items.items.properties.code.maximum'                 => 2,
                'products.type'                                       => 'array',
                'order.type'                                          => 'object',
                'order.properties.id.type'                            => 'string',
                'order.properties.address.properties.address.type'    => 'string',
                'order.properties.address.properties.postalCode.type' => 'string',
            ],
            $properties
        );
    }

    /**
     * @test
     */
    public function it_calculates_all_required_properties()
    {
        $batch = Batch::factory()
            ->has(Entry::factory()->response())
            ->has(Entry::factory()->parameters())
            ->has(Entry::factory([
                'type'    => Entry::TYPE_VALIDATION_RULES,
                'content' => [
                    'email'    => ['required', 'email'],
                    'name'     => ['nullable', 'string'],
                    'birthday' => ['required', 'date_format:d - m - Y'],
                ],
            ]))
            ->create();

        $data = new PathData($batch);

        $requiredProperties = $this->property($data, 'requiredProperties')->getValue($data);

        $this->assertArrayHasValues(['email', 'birthday'], $requiredProperties);
    }

    /**
     * @test
     */
    public function it_calculates_all_the_response_content_for_json_responses()
    {
        $batch = Batch::factory(['response_code' => 200])
            ->has(Entry::factory()->validation([]))
            ->has(Entry::factory()->response(response()->json([
                'id'       => 23,
                'name'     => 'Rico Nijeboer',
                'email'    => 'rico@riconijeboer.nl',
                'birthday' => '1998-02-04',
                'number'   => 102.1240,
                'roles'    => ['developer'],
                'foo'      => ['bar' => 'baz'],
            ])->prepare(new Request())))
            ->has(Entry::factory()->parameters())
            ->create();

        $data = new PathData($batch);

        $response = $this->property($data, 'response')->getValue($data);

        $this->assertArrayHasKeys(
            [
                'code'                                      => 200,
                'contentType'                               => 'application/json',
                'schema.type'                               => 'object',
                'schema.properties.id.type'                 => 'integer',
                'schema.properties.name.type'               => 'string',
                'schema.properties.email.type'              => 'string',
                'schema.properties.email.format'            => 'email',
                'schema.properties.number.type'             => 'number',
                'schema.properties.birthday.type'           => 'string',
                'schema.properties.roles.type'              => 'array',
                'schema.properties.roles.items.type'        => 'string',
                'schema.properties.foo.type'                => 'object',
                'schema.properties.foo.properties.bar.type' => 'string',
            ],
            $response
        );
    }

    /**
     * @test
     */
    public function it_calculate_all_parameters()
    {
        $batch = Batch::factory(['route_uri' => 'batches/{batch}'])
            ->has(Entry::factory()->response())
            ->has(Entry::factory()->validation([]))
            ->has(Entry::factory()->parameters([
                'batch' => [
                    'class'    => Batch::class,
                    'required' => true,
                ],
            ]))
            ->create();

        $data = new PathData($batch);

        $parameters = $this->property($data, 'parameters')->getValue($data);

        $this->assertIsArray($parameters);
        $this->assertArrayHasKeys(
            [
                '0.in'          => 'path',
                '0.name'        => 'batch',
                '0.schema.type' => 'string',
                '0.required'    => true,
                '0.description' => 'Batch',
            ],
            $parameters
        );
    }

    /**
     * @test
     */
    public function it_shows_when_a_parameter_is_optional()
    {
        $batch = Batch::factory(['route_uri' => 'batches/{batch}'])
            ->has(Entry::factory()->response())
            ->has(Entry::factory()->validation([]))
            ->has(Entry::factory()->parameters([
                'batch' => [
                    'class'    => Batch::class,
                    'required' => false,
                ],
            ]))
            ->create();

        $data = new PathData($batch);

        $parameters = $this->property($data, 'parameters')->getValue($data);

        $this->assertIsArray($parameters);
        $this->assertArrayHasKeys(
            [
                '0.in'          => 'path',
                '0.name'        => 'batch',
                '0.schema.type' => 'string',
                '0.required'    => false,
                '0.description' => 'Batch',
            ],
            $parameters
        );
    }

    /**
     * @test
     */
    public function it_stores_an_empty_array_of_parameters_when_the_batch_does_not_have_an_entry_for_parameters()
    {
        $batch = Batch::factory(['route_uri' => 'batches/{batch}'])
            ->has(Entry::factory()->response())
            ->has(Entry::factory()->validation([]))
            ->create();

        $data = new PathData($batch);

        $parameters = $this->property($data, 'parameters')->getValue($data);

        $this->assertIsArray($parameters);
        $this->assertEmpty($parameters);
    }

    /**
     * @test
     */
    public function it_stores_a_server_when_the_route_domain_column_is_set()
    {
        /** @var Batch $batch */
        $batch = Batch::factory(['route_uri' => 'products', 'route_method' => 'GET', 'response_code' => 204, 'route_domain' => 'echo.example.com'])
            ->has(Entry::factory()->validation())
            ->has(Entry::factory()->response())
            ->has(Entry::factory()->parameters())
            ->create();

        $data = new PathData($batch);

        $servers = $this->property($data, 'servers')->getValue($data);

        $this->assertIsArray($servers);
        $this->assertCount(1, $servers);
        $this->assertArrayHasKeys(['0.url' => 'echo.example.com'], $servers);
    }
}
