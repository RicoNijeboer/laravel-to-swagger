<?php

namespace RicoNijeboer\Swagger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use RicoNijeboer\Swagger\Models\Entry;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

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

    public function response(SymfonyResponse $response = null): self
    {
        return $this->state(fn () => [
            'type'    => Entry::TYPE_RESPONSE,
            'content' => [
                'contentType' => is_null($response) ? 'application/json' : $response->headers->get('Content-Type'),
                'response'    => is_null($response) ? [
                    'id'   => $this->faker->randomNumber(),
                    'name' => $this->faker->name,
                ] : $response->getContent(),
            ],
        ]);
    }

    public function middleware(array $middleware = []): self
    {
        return $this->state(fn () => [
            'type'    => Entry::TYPE_ROUTE_MIDDLEWARE,
            'content' => $middleware ?? [],
        ]);
    }

    public function validation(array $rules = null): self
    {
        return $this->state(fn () => [
            'type'    => Entry::TYPE_VALIDATION_RULES,
            'content' => $rules ?? [
                    'email' => ['required', 'email'],
                    'name'  => ['required', 'string'],
                ],
        ]);
    }

    public function parameters(array $parameters = null): self
    {
        return $this->state(fn () => [
            'type'    => Entry::TYPE_ROUTE_PARAMETERS,
            'content' => $parameters ?? [],
        ]);
    }

    public function wheres(array $wheres = null): self
    {
        return $this->state(fn () => [
            'type'    => Entry::TYPE_ROUTE_WHERES,
            'content' => $wheres ?? [],
        ]);
    }
}
