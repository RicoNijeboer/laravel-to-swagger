<?php

namespace RicoNijeboer\Swagger\Data;

use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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

    public array $requiredProperties = [];

    public array $properties = [];

    public array $response = [
        'code'        => Response::HTTP_OK,
        'contentType' => 'text/plain',
        'schema'      => ['type' => 'string'],
    ];

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

        $this->calculateProperties($batch)
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

                $this->response['schema'] = ValueHelper::jsonResponseProperty(is_string($responseBody) ? json_decode($responseBody, true) : $responseBody);
                break;
            case 'text/html':
                $this->response['schema'] = [
                    'type' => 'string',
                ];
                break;
        }
    }
}
