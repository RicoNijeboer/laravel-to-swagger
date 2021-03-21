<?php

namespace Rico\Swagger\Swagger;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rico\Reader\Endpoints\EndpointData;

/**
 * Class Endpoint
 *
 * @package Rico\Swagger\Swagger
 */
class Endpoint
{

    private EndpointData $originalData;

    private array $required = [];

    private array $parameters = [];

    private array $properties = [];

    public function __construct(EndpointData $data)
    {
        $this->loadFrom($data);
    }

    /**
     * @param EndpointData $data
     *
     * @return $this
     */
    public function loadFrom(EndpointData $data): self
    {
        $this->required = [];
        $this->parameters = [];
        $this->properties = [];
        $this->originalData = $data;

        $this->loadParameters($data);
        $this->loadProperties($data);
        $this->loadRequiredProperties($data);

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        $array = [
            'responses' => [
                '200' => [
                    'description' => 'OK',
                ],
            ],
        ];

        if (count($this->parameters) > 0)
        {
            $array['parameters'] = $this->parameters;
        }

        if (count($this->properties) > 0)
        {
            $array['requestBody'] = [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type'       => 'object',
                            'properties' => $this->properties,
                        ],
                    ],
                ],
            ];

            if (count($this->required) > 0)
            {
                Arr::set($array, 'requestBody.content.application/json.schema.required', $this->required);
            }
        }

        return $array;
    }

    /**
     * @param EndpointData $data
     *
     * @return void
     */
    protected function loadProperties(EndpointData $data): void
    {
        $item = [];
        $data->properties()
             ->sortKeys()
             ->each(function ($_, $prop) use (&$item) {
                 if (Str::contains($prop, '*'))
                 {
                     [$parent] = explode('.*.', $prop, 2);

                     Arr::set($item, $parent . '.__type', 'array');
                     Arr::set($item, str_replace('.*.', '.', $prop), []);

                     return;
                 }

                 Arr::set($item, $prop, []);
             });

        $this->properties = collect($item)
            ->mapWithKeys(function (array $value, string $property) {
                return $this->loadProperty($value, $property);
            })
            ->toArray();
    }

    /**
     * @param array  $value
     * @param string $property
     *
     * @return array[]|string[][]
     */
    protected function loadProperty(array $value, string $property): array
    {
        if (count($value) > 0)
        {
            if ($value['__type'] ?? '' === 'array')
            {
                unset($value['__type']);

                return [
                    $property => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => collect($value)
                                ->mapWithKeys(fn(array $value, string $property) => $this->loadProperty($value, $property))
                                ->all(),
                        ],
                    ],
                ];
            }

            return [
                $property => [
                    'type'       => 'object',
                    'properties' => collect($value)
                        ->mapWithKeys(fn(array $value, string $property) => $this->loadProperty($value, $property))
                        ->all(),
                ],
            ];
        }

        return [
            $property => [
                'type' => 'string',
            ],
        ];
    }

    /**
     * @param EndpointData $data
     */
    protected function loadParameters(EndpointData $data)
    {
        $route = $data->route();
        $parameters = $route->parameters();

        $this->parameters = array_map(
            function (string $parameter, string $definition) {
                $required = ! Str::is($definition, '{?*}');

                $parameterData = [
                    'in'     => 'path',
                    'name'   => $parameter,
                    'schema' => [
                        'type' => 'string',
                    ],
                ];

                if ($required)
                {
                    $parameterData['required'] = true;
                }

                return $parameterData;
            },
            array_keys($parameters),
            $parameters
        );
    }

    /**
     * @param EndpointData $data
     */
    protected function loadRequiredProperties(EndpointData $data)
    {
        $this->required = $data->required()->toArray();
    }
}