<?php

namespace RicoNijeboer\Swagger\Actions;

use Illuminate\Container\Container;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\LazyCollection;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use RicoNijeboer\Swagger\Data\PathData;
use RicoNijeboer\Swagger\Exceptions\MalformedServersException;
use RicoNijeboer\Swagger\Models\Batch;
use RicoNijeboer\Swagger\Support\Concerns\HelperMethods;

/**
 * Class BuildOpenApiConfig
 *
 * @package RicoNijeboer\Swagger\Actions
 */
class BuildOpenApiConfigAction
{
    use HelperMethods;

    private ComputeSecuritySchemesAction $securitySchemes;

    public function __construct(ComputeSecuritySchemesAction $securitySchemes)
    {
        $this->securitySchemes = $securitySchemes;
    }

    /**
     * @return array
     * @throws MalformedServersException
     */
    public function build(): array
    {
        $openApi = [
            'openapi' => '3.0.0',
            'info'    => $this->getInfo(),
            'paths'   => $this->getPaths(),
            'servers' => $this->getServers(),
        ];

        $oAuth2Schemes = $this->securitySchemes->oAuth2Schemes();

        if (!is_null($oAuth2Schemes)) {
            Arr::set($openApi, 'components.securitySchemes', $oAuth2Schemes);
        }

        $this->applyRedocConfig($openApi);

        return $openApi;
    }

    /**
     * @return array
     */
    protected function getInfo(): array
    {
        $info = array_filter([
            'title'       => config('swagger.info.title'),
            'description' => config('swagger.info.description'),
            'version'     => config('swagger.info.version'),
        ]);

        $logo = array_filter([
            'url'             => config('swagger.info.logo.url'),
            'backgroundColor' => config('swagger.info.logo.background-color'),
            'altText'         => config('swagger.info.logo.alt-text'),
        ]);

        if (\array_key_exists('url', $logo)) {
            $info['x-logo'] = $logo;
        }

        return $info;
    }

    /**
     * @return array
     * @throws MalformedServersException
     */
    protected function getServers(): array
    {
        $validator = new Validator(resolve(Translator::class), ['servers' => config('swagger.servers', [])], [
            'servers'               => [
                'array',
                'min:0',
            ],
            'servers.*.url'         => ['required'],
            'servers.*.description' => ['nullable', 'string'],
            'servers.*.variables'   => ['nullable', 'array', 'min:0'],
            'servers.*.variables.*' => ['array'],
        ]);
        $validator->setContainer(Container::getInstance());

        try {
            $validator->validate();

            $servers = [];
            foreach ($this->recursively(config('swagger.servers', [])) as [$item, $key]) {
                if (!is_null($item)) {
                    Arr::set($servers, $key, $item);
                }
            }

            return $servers;
        } catch (ValidationException $e) {
            throw new MalformedServersException($e->validator->errors());
        }
    }

    /**
     * @return array
     */
    protected function getPaths(): array
    {
        $paths = [];
        $this->getBatches()
            ->map(function (Batch $batch) {
                return $this->buildBatchConfig($batch);
            })
            ->each(function (array $path) use (&$paths) {
                $uri = key($path);
                $method = key($path[$uri]);

                if (array_key_exists($uri, $paths)) {
                    if (array_key_exists($method, $paths[$uri])) {
                        foreach ($path[$uri][$method]['responses'] as $responseCode => $response) {
                            $paths[$uri][$method]['responses'][$responseCode] = $response;
                        }

                        return;
                    }

                    $paths[$uri][$method] = $path[$uri][$method];

                    return;
                }

                $paths[$uri] = $path[$uri];
            });

        return $paths;
    }

    protected function getBatches(): LazyCollection
    {
        return Batch::query()
            ->with([
                'validationRulesEntry',
                'responseEntry',
            ])
            ->whereHas('validationRulesEntry')
            ->whereHas('responseEntry')
            ->cursor();
    }

    /**
     * @param Batch $batch
     *
     * @return array[][]
     */
    protected function buildBatchConfig(Batch $batch): array
    {
        $pathData = new PathData($batch);

        $batchConfig = [
            'summary'   => $pathData->summary,
            'responses' => [
                $pathData->response['code'] => [
                    'description' => 'Some description',
                ],
            ],
        ];

        if ($pathData->response['code'] !== Response::HTTP_NO_CONTENT) {
            $batchConfig['responses'][$pathData->response['code']]['content'] = [
                $pathData->response['contentType'] => $pathData->response,
            ];
        }

        if (!empty($pathData->parameters)) {
            $batchConfig['parameters'] = $pathData->parameters;
        }

        if (!empty($pathData->security)) {
            $batchConfig['security'] = $pathData->security;
        }

        if (!empty($pathData->servers)) {
            $batchConfig['servers'] = $pathData->servers;
        }

        if ($batch->tags()->exists()) {
            $batchConfig['tags'] = $batch->tags->pluck('tag')->toArray();
        }

        if (count($batch->validationRulesEntry->content) > 0) {
            // It has request body.
            $batchConfig['requestBody'] = [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type'       => 'object',
                            'properties' => $pathData->properties,
                        ],
                    ],
                ],
            ];
        }

        return [
            $pathData->uri => [
                $pathData->method => $batchConfig,
            ],
        ];
    }

    protected function applyRedocConfig(array &$openApi)
    {
        $tagGroups = Config::get('swagger.redoc.tag-groups', []);
        $defaultGroup = Config::get('swagger.redoc.default-group') ?? 'Default';

        if (empty($tagGroups)) {
            return;
        }

        $openApi['x-tagGroups'] = $tagGroups;

        $groupedTags = collect($tagGroups)
            ->flatMap(function (array $tagGroup) {
                return $tagGroup['tags'];
            })
            ->toArray();
        $ungroupedTags = collect($openApi['paths'])
            ->flatMap(
                fn (array $path) => collect($path)
                    ->flatMap(fn (array $pathData) => $pathData['tags'] ?? [])
                    ->filter(fn (string $tag) => !in_array($tag, $groupedTags))
            )
            ->toArray();

        if (!empty($ungroupedTags)) {
            array_unshift($openApi['x-tagGroups'], [
                'name' => $defaultGroup,
                'tags' => $ungroupedTags,
            ]);
        }
    }
}
