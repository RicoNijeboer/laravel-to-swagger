<?php

namespace RicoNijeboer\Swagger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use RicoNijeboer\Swagger\Models\Entry;

/**
 * Class EntryFactory
 *
 * @package RicoNijeboer\Swagger\Database\Factories
 * @method Entry create($attributes = [], ?Model $parent = null)
 */
class EntryFactory extends Factory
{
    protected $model = Entry::class;

    public function definition(): array
    {
        return [];
    }

    public function response(): self
    {
        return $this->state(function () {
            return [
                'type'    => Entry::TYPE_RESPONSE,
                'content' => [
                    'contentType' => 'application/json',
                    'response'    => [
                        'id'   => $this->faker->randomNumber(),
                        'name' => $this->faker->name,
                    ],
                ],
            ];
        });
    }

    public function validation(): self
    {
        return $this->state(function () {
            return [
                'type'    => Entry::TYPE_VALIDATION_RULES,
                'content' => [
                    'contentType' => 'application/json',
                    'response'    => [
                        'email' => ['required', 'email'],
                        'name'  => ['required', 'string'],
                    ],
                ],
            ];
        });
    }
}
