<?php

namespace RicoNijeboer\Swagger\Database\Factories;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use RicoNijeboer\Swagger\Models\Batch;

/**
 * Class BatchFactory
 *
 * @package RicoNijeboer\Swagger\Database\Factories
 * @method Batch create($attributes = [], ?Model $parent = null)
 */
class BatchFactory extends Factory
{
    protected $model = Batch::class;

    public function definition(): array
    {
        /** @var \Illuminate\Foundation\Http\Kernel $kernel */
        $kernel = resolve(Kernel::class);

        return [
            'route_uri'        => $this->faker->slug(3),
            'route_name'       => str_replace('-', '.', $this->faker->url),
            'route_method'     => $this->faker->randomElement([
                Request::METHOD_GET,
                Request::METHOD_POST,
                Request::METHOD_PUT,
                Request::METHOD_PATCH,
                Request::METHOD_DELETE,
                Request::METHOD_OPTIONS,
            ]),
            'route_middleware' => $this->faker->randomElements($kernel->getRouteMiddleware(), 3),
        ];
    }
}
