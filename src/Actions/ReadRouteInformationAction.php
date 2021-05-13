<?php

namespace RicoNijeboer\Swagger\Actions;

use Exception;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
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

    public function read(SymfonyRequest $request, Route $route, SymfonyResponse $response, array $rules = [])
    {
        $batch = $this->createBatch(strtoupper($request->getMethod()), $route, $response);

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
            $obfuscated = [];

            $this->recursively(json_decode($responseContent, true), function ($item, $key) use (&$obfuscated) {
                if (!is_array($item)) {
                    Arr::set($obfuscated, $key, $this->obfuscate($item));
                }
            });

            $responseContent = json_encode($obfuscated);
        }

        return $responseContent;
    }

    /**
     * @param mixed $item
     *
     * @return mixed
     * @throws Exception
     */
    protected function obfuscate($item, bool $ensureDifferentValue = true, int $maxTries = 10)
    {
        if ($ensureDifferentValue) {
            $tries = 0;
            $value = $item;

            do {
                $tries++;
                $value = $this->obfuscate($value, false);
            } while ($item === $value || $tries < $maxTries);

            if ($tries === $maxTries && $item === $value) {
                throw new Exception("Maximum tries of [{$maxTries}] reached.");
            }

            return $value;
        }

        if (is_numeric($item)) {
            if (is_int($item)) {
                return rand();
            }

            // Check if $item has exactly 2 decimals
            if (floor($item * 100) === $item * 100) {
                return (float)(rand() . ".01");
            }

            return (float)(rand() . ".0123456789");
        }

        if (is_string($item)) {
            $time = strtotime($item);

            if ($time !== false) {
                $diff = rand(60000, 120000);
                $diff = $time > time() ? $diff : (-1 * $diff);
                $format = $this->getDateFormat($time);

                return date($format, time() + $diff);
            }

            return uniqid('string-');
        }

        return $item;
    }
}
