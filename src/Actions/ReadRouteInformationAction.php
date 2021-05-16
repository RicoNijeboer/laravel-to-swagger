<?php

namespace RicoNijeboer\Swagger\Actions;

use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use RicoNijeboer\Swagger\Models\Batch;
use RicoNijeboer\Swagger\Models\Entry;
use RicoNijeboer\Swagger\Support\Concerns\HelperMethods;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Class ReadRouteInformationAction
 *
 * @package RicoNijeboer\Swagger\Actions
 */
class ReadRouteInformationAction
{
    use HelperMethods;

    private ObfuscateJsonAction $obfuscate;

    public function __construct(ObfuscateJsonAction $obfuscate)
    {
        $this->obfuscate = $obfuscate;
    }

    public function read(SymfonyRequest $request, Route $route, SymfonyResponse $response, array $rules = [])
    {
        $batch = $this->createBatch(strtoupper($request->getMethod()), $route, $response);

        $parametersEntry = $this->createParametersEntry($batch, $route);
        $rulesEntry = $this->createRulesEntry($batch, $rules);
        $responseEntry = $this->createResponseEntry($batch, $response);
    }

    protected function createBatch(string $method, Route $route, SymfonyResponse $response): Batch
    {
        $batch = new Batch();

        $batch->response_code = $response->getStatusCode();
        $batch->route_method = strtoupper($method);
        $batch->route_uri = $route->uri();
        $batch->route_name = $route->getName();
        $batch->route_domain = $route->getDomain();
        $batch->route_middleware = $route->gatherMiddleware();

        $batch->save();

        return $batch;
    }

    protected function createRulesEntry(Batch $batch, array $rules = []): Entry
    {
        $entry = new Entry();
        $entry->batch()->associate($batch);

        $entry->type = Entry::TYPE_VALIDATION_RULES;
        $entry->content = $rules;

        $entry->save();

        return $entry;
    }

    protected function createResponseEntry(Batch $batch, SymfonyResponse $response): Entry
    {
        $entry = new Entry();
        $entry->batch()->associate($batch);

        $entry->type = Entry::TYPE_RESPONSE;
        $entry->content = [
            'contentType' => $response->headers->get('Content-Type'),
            'response'    => $this->obfuscateResponse($response),
        ];

        $entry->save();

        return $entry;
    }

    protected function createParametersEntry(Batch $batch, Route $route): Entry
    {
        $entry = new Entry();
        $entry->batch()->associate($batch);

        $entry->type = Entry::TYPE_ROUTE_PARAMETERS;
        $entry->content = collect($route->parameters())->mapWithKeys(function ($value, string $parameter) use ($route) {
            $parameterValue = $route->parameter($parameter);

            preg_match("/\{({$parameter})(:[\w]*)?\}/", $route->uri(), $matches);

            return [
                $parameter => [
                    'class'    => !is_object($parameterValue) ? 'string' : get_class($parameterValue),
                    'required' => count($matches) > 0,
                ],
            ];
        });

        $entry->save();

        return $entry;
    }

    /**
     * @param SymfonyResponse $response
     *
     * @return false|string
     */
    protected function obfuscateResponse(SymfonyResponse $response)
    {
        $responseContent = $response->getContent();
        $contentType = $response->headers->get('Content-Type');

        if (Str::contains($contentType, 'application/json')) {
            $responseContent = $this->obfuscate->obfuscateJson($responseContent);
        }

        return $responseContent;
    }
}
