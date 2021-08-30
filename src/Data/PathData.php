<?php

namespace RicoNijeboer\Swagger\Data;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Laravel\Passport\Http\Middleware\CheckForAnyScope;
use Laravel\Passport\Http\Middleware\CheckScopes;
use RicoNijeboer\Swagger\Models\Batch;
use RicoNijeboer\Swagger\Support\RuleHelper;
use RicoNijeboer\Swagger\Support\ValueHelper;

/**
 * Class PathData
 *
 * @package RicoNijeboer\Swagger\Data
 */
class PathData
{
    public string $uri;

    public string $method;

    public string $summary;

    public array $servers = [];

    public array $parameters = [];

    public array $requiredProperties = [];

    public array $properties = [];

    public array $response = [
        'code'        => Response::HTTP_OK,
        'contentType' => 'text/plain',
        'schema'      => ['type' => 'string'],
    ];

    public array $security = [];

    protected Batch $batch;

    /**
     * PathData constructor.
     *
     * @param Batch $batch
     */
    public function __construct(Batch $batch)
    {
        $this->batch = $batch;

        $this->init($batch);
    }

    /**
     * @param Batch $batch
     */
    protected function init(Batch $batch)
    {
        $this->uri = Str::start($batch->route_uri, '/');
        $this->method = strtolower($batch->route_method);
        $this->summary = $batch->route_name ?? $batch->route_uri;

        $this->calculateServers($batch)
            ->calculateParameters($batch)
            ->calculateMiddleware($batch)
            ->calculateProperties($batch)
            ->calculateRequired($batch)
            ->calculateResponse($batch);
    }

    /**
     * @param Batch $batch
     *
     * @return $this
     */
    protected function calculateProperties(Batch $batch): self
    {
        $ruleCache = [];
        $rules = [];

        foreach ($batch->validationRulesEntry->content->collect() as $property => $propertyRules) {
            $filtered = array_filter($propertyRules, fn ($rule) => !is_array($rule));
            $starProp = preg_replace('/\.[0-9]+/m', '.*', $property);

            Arr::set(
                $rules,
                $starProp,
                $filtered
            );

            $ruleCache[$starProp] = $filtered;
        }

        $this->properties = collect($rules)
            ->map(fn (array $rules, string $property) => RuleHelper::openApiProperty($property, $rules, $ruleCache))
            ->toArray();

        return $this;
    }

    /**
     * @param Batch $batch
     *
     * @return $this
     */
    protected function calculateRequired(Batch $batch): self
    {
        $this->requiredProperties = $batch->validationRulesEntry->content->collect()
            ->filter(function (array $rules) {
                return RuleHelper::isRequired($rules);
            })
            ->keys()
            ->toArray();

        return $this;
    }

    /**
     * @param Batch $batch
     *
     * @return $this
     */
    protected function calculateResponse(Batch $batch): self
    {
        $contentType = Arr::first(
            explode(';', $batch->responseEntry->content['contentType']),
            fn (string $contentType) => Str::is('*/*', $contentType)
        );

        $this->response = [
            'code'        => $batch->response_code,
            'contentType' => trim($contentType),
            'schema'      => [],
        ];
        $this->calculateResponseSchema($batch);

        return $this;
    }

    /**
     * @param Batch $batch
     */
    protected function calculateResponseSchema(Batch $batch)
    {
        switch ($this->response['contentType']) {
            case 'application/json':
                $responseBody = $batch->responseEntry->content['response'];

                $exampleBody = is_string($responseBody) ? json_decode($responseBody, true) : $responseBody;

                $this->response['schema'] = ValueHelper::jsonResponseProperty($exampleBody);
                $this->response['example'] = $exampleBody;
                break;
            case 'text/html':
                $this->response['schema'] = [
                    'type' => 'string',
                ];
                break;
        }
    }

    /**
     * @param Batch $batch
     *
     * @return $this
     */
    protected function calculateParameters(Batch $batch): self
    {
        if ($batch->parameterEntry()->doesntExist()) {
            $this->parameters = [];

            return $this;
        }

        $formats = optional(optional($batch->parameterWheresEntry)->content)->getArrayCopy() ?? [];

        $this->parameters = collect($batch->parameterEntry->content)
            ->map(function (array $data, string $parameter) use ($formats) {
                ['class' => $class, 'required' => $required] = $data;

                $hasFormat = array_key_exists($parameter, $formats);

                return [
                    'in'          => 'path',
                    'required'    => $required,
                    'name'        => $parameter,
                    'schema'      => array_merge([
                        'type' => 'string',
                    ], array_filter([
                        'format' => $hasFormat ? $formats[$parameter] : null,
                    ])),
                    'description' => $class === 'string' ? Str::studly($parameter) : class_basename($class),
                ];
            })
            ->values()
            ->all();

        return $this;
    }

    /**
     * @param Batch $batch
     *
     * @return $this
     */
    protected function calculateServers(Batch $batch): PathData
    {
        $this->servers = [];

        if (!empty($batch->route_domain)) {
            $this->servers[] = [
                'url' => $batch->route_domain,
            ];
        }

        return $this;
    }

    /**
     * @param Batch $batch
     *
     * @return $this
     */
    protected function calculateMiddleware(Batch $batch): PathData
    {
        if ($batch->middlewareEntry()->doesntExist()) {
            $this->security = [];

            return $this;
        }

        $allMiddleware = collect($batch->middlewareEntry->content);
        $middlewareOfInstance = function ($instance, bool $collection = true) use ($allMiddleware) {
            $instance = Arr::wrap($instance);

            $filter = $collection ? 'filter' : 'first';

            return $allMiddleware
                ->{$filter}(function (string $middleware) use ($instance) {
                    $middleware = resolve(explode(':', $middleware, 2)[0]);

                    foreach ($instance as $class) {
                        if ($middleware instanceof $class) {
                            return true;
                        }
                    }

                    return false;
                });
        };

        /** @var Collection $authenticateMiddlewares */
        $authenticateMiddlewares = $middlewareOfInstance(Authenticate::class);

        $this->security = $authenticateMiddlewares
            ->map(function (string $middleware) use ($middlewareOfInstance) {
                $parameters = explode(':', $middleware, 2)[1] ?? '';
                $parameters = array_filter(explode(',', $parameters));

                if (empty($parameters)) {
                    $parameters[] = config('auth.defaults.guard');
                }

                return collect($parameters)
                    ->map(function (string $parameter) use ($middlewareOfInstance) {
                        /** @var Collection $checkScopesMiddleware */
                        $checkScopesMiddleware = $middlewareOfInstance([CheckScopes::class, CheckForAnyScope::class]);

                        if (Config::get("auth.guards.{$parameter}.driver") === 'passport' && !empty($checkScopesMiddleware)) {
                            $scopes = $checkScopesMiddleware
                                ->flatMap(fn (string $middleware) => explode(',', explode(':', $middleware, 2)[1]))
                                ->values()
                                ->toArray();

                            return [
                                $parameter => $scopes,
                            ];
                        }

                        return $parameter;
                    })
                    ->all();
            })
            ->flatten(1)
            ->values()
            ->toArray();

        return $this;
    }
}
