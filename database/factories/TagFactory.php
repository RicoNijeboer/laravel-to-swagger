<?php

namespace RicoNijeboer\Swagger\Database\Factories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use RicoNijeboer\Swagger\Models\Tag;

/**
 * Class TagFactory
 *
 * @package RicoNijeboer\Swagger\Database\Factories
 * @method Tag[]|Collection|Tag create($attributes = [], ?Model $parent = null)
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return [
            'tag' => $this->faker->word,
        ];
    }
}
