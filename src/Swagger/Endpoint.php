<?php

namespace Rico\Swagger\Swagger;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Rico\Reader\Endpoints\DataType;
use Rico\Reader\Endpoints\DataType as ReaderDataType;
use Rico\Reader\Endpoints\EndpointData;
use Rico\Reader\Exceptions\PropertyIsNotInRulesException;

/**
 * Class Endpoint
 *
 * @package Rico\Swagger\Swagger
 */
class Endpoint
{
    protected const DATA_TYPES = [
        ReaderDataType::STRING             => 'string',
        ReaderDataType::INT                => 'integer',
        ReaderDataType::FLOAT              => 'number',
        ReaderDataType::DATETIME           => 'string',
        ReaderDataType::FORMATTED_DATETIME => 'string',
        ReaderDataType::BOOLEAN            => 'boolean',
        ReaderDataType::ARRAY              => 'array',
        ReaderDataType::OBJECT             => 'object',
        ReaderDataType::DOUBLE             => 'double',
    ];
    private EndpointData $originalData;
    private array $required = [];
    private array $parameters = [];
    private array $properties = [];
    /** @var string[] */
    private array $tags = [];
    private array $security = [];

    public function __construct(EndpointData $data)
    {
        $this->loadFrom($data);
    }

    /**
     * @param EndpointData $data
     *
     * @return $this
     * @throws PropertyIsNotInRulesException
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
            'summary' => $this->originalData->route()->uri(),
            'responses' => [
                '200' => [
                    'description' => 'OK',
                ],
            ],
        ];

        if (count($this->tags) > 0) {
            $array['tags'] = array_map(fn (Tag $tag): string => $tag, $this->tags);
        }

        if (count($this->parameters) > 0) {
            $array['parameters'] = $this->parameters;
        }

        if (count($this->properties) > 0) {
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

            if (count($this->required) > 0) {
                Arr::set($array, 'requestBody.content.application/json.schema.required', $this->required);
            }
        }

        if (count($this->security) > 0) {
            $array['security'] = $this->security;
        }

        return $array;
    }

    /**
     * Apply tags to the endpoint.
     *
     * @param Tag[] $tags
     *
     * @return $this
     */
    public function applyTags(array $tags): self
    {
        $this->tags = array_values(
            array_filter($tags, fn (Tag $tag) => $tag->matches($this->originalData->route()))
        );

        return $this;
    }

    /**
     * @return string
     */
    public function uri(): string
    {
        return Str::start($this->originalData->route()->uri(), '/');
    }

    /**
     * @return string[]
     */
    public function middlewares(): array
    {
        return $this->originalData->route()->gatherMiddleware();
    }

    /**
     * Returns the controller used by the endpoint.
     * When the route uses a Closure it will return "Closure"
     *
     * @return string
     */
    public function controller(): string
    {
        [$controller] = $this->getAction();

        return $controller;
    }

    /**
     * Returns the controller used by the endpoint.
     * When the route uses a Closure it will return "Closure"
     *
     * @return string|null
     */
    public function controllerMethod(): ?string
    {
        $action = $this->getAction();

        return $action[1] ?? null;
    }

    /**
     * @param string $securityScheme
     * @param array  $scopes
     *
     * @return $this
     */
    public function addSecurity(string $securityScheme, array $scopes): self
    {
        $this->security[] = [
            $securityScheme => $scopes,
        ];

        return $this;
    }

    /**
     * @param EndpointData $data
     *
     * @return void
     * @throws PropertyIsNotInRulesException
     */
    protected function loadProperties(EndpointData $data): void
    {
        $item = [];
        $data->properties()
            ->sortKeys()
            ->each(function ($_, $prop) use ($data, &$item) {
                $extraInfo = [
                    'nullable' => $data->isNullable($prop),
                ];

                if ($data->hasRule($prop, 'min:*')) {
                    [, $extraInfo['min']] = explode(':', $data->getRule($prop, 'min:*'));
                }

                if ($data->hasRule($prop, 'max:*')) {
                    [, $extraInfo['max']] = explode(':', $data->getRule($prop, 'max:*'));
                }

                if ($data->isEmail($prop)) {
                    $extraInfo['isEmail'] = true;
                }

                try {
                    $type = $this->originalData->dataType($prop);
                } catch (PropertyIsNotInRulesException $e) {
                    $type = null;
                }

                if (Str::contains($prop, '.*.')) {
                    [$parent] = explode('.*.', $prop, 2);

                    Arr::set(
                        $item,
                        $parent . '.__type',
                        ReaderDataType::ARRAY
                    );

                    Arr::set(
                        $item,
                        str_replace('.*.', '.', $prop),
                        [
                            '__type'  => $type,
                            '__extra' => $extraInfo,
                        ]
                    );

                    return;
                } elseif (Str::endsWith($prop, '.*')) {
                    $parent = substr($prop, 0, strlen($prop) - 2);

                    Arr::set(
                        $item,
                        $parent . '.__type',
                        ReaderDataType::ARRAY
                    );

                    Arr::set(
                        $item,
                        $parent . '.0',
                        [
                            '__type'  => $type,
                            '__extra' => $extraInfo,
                        ]
                    );

                    return;
                }

                Arr::set($item, $prop, [
                    '__type'  => $type,
                    '__extra' => $extraInfo,
                ]);
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
        $type = $value['__type'] ?? ReaderDataType::OBJECT;
        unset($value['__type']);
        $extraInfo = $value['__extra'] ?? [];
        unset($value['__extra']);

        $swaggerInfo = Property::makeData(static::DATA_TYPES[$type], $value, $extraInfo);

        if ($type === DataType::OBJECT) {
            $swaggerInfo['properties'] = collect($value)
                ->mapWithKeys(fn (array $value, string $property) => $this->loadProperty($value, $property))
                ->all();
        } elseif ($type === DataType::ARRAY) {
            $firstPropertyKey = array_keys($value)[0];

            if (is_int($firstPropertyKey) && count($value) === 1) {
                $firstProperty = $value[$firstPropertyKey];

                $firstPropertyType = $firstProperty['__type'] ?? ReaderDataType::OBJECT;
                unset($firstProperty['__type']);
                $firstPropertyExtraInfo = $firstProperty['__extra'] ?? [];
                unset($firstProperty['__extra']);

                $swaggerInfo['items'] = Property::makeData(static::DATA_TYPES[$firstPropertyType], $firstProperty, $firstPropertyExtraInfo);
            } else {
                $swaggerInfo['items'] = [
                    'type'       => 'object',
                    'properties' => collect($value)
                        ->mapWithKeys(fn (array $value, string $property) => $this->loadProperty($value, $property))
                        ->all(),
                ];
            }
        }

        return [
            $property => $swaggerInfo,
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
                $required = !Str::is($definition, '{?*}');

                $parameterData = [
                    'in'     => 'path',
                    'name'   => $parameter,
                    'schema' => [
                        'type' => 'string',
                    ],
                ];

                if ($required) {
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
        $this->required = $data->required();
    }

    /**
     * Returns [controller, action] if it can.
     * For routes with closures it returns ["Closure"].
     *
     * @return string[]
     */
    protected function getAction(): array
    {
        $route = $this->originalData->route();

        return explode('@', $route->getActionName(), 2);
    }
}