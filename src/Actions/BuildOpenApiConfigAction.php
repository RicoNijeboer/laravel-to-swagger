<?php

namespace RicoNijeboer\Swagger\Actions;

use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\LazyCollection;
use RicoNijeboer\Swagger\Data\PathData;
use RicoNijeboer\Swagger\Models\Batch;

/**
 * Class BuildOpenApiConfig
 *
 * @package RicoNijeboer\Swagger\Actions
 */
class BuildOpenApiConfigAction
{
    private ComputeSecuritySchemesAction $securitySchemes;

    public function __construct(ComputeSecuritySchemesAction $securitySchemes)
    {
        $this->securitySchemes = $securitySchemes;
    }

    public function build()
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

        return $openApi;
    }

    /**
     * @return array
     */
    protected function getInfo(): array
    {
        return array_filter([
            'title'       => config('swagger.info.title'),
            'description' => config('swagger.info.description'),
            'version'     => config('swagger.info.version'),
        ]);
    }

    /**
     * @return array
     */
    protected function getServers(): array
    {
        /** @var array $servers */
        $servers = config('swagger.servers', []);

        return array_filter(
            array_map(
                fn (array $server) => array_filter([
                    'url'         => $server['url'],
                    'description' => $server['description'] ?? null,
                ]),
                $servers
            )
        );
    }

    /**
     * @return array
     */
    protected function getPaths(): array
    {
        $batches = $this->getBatches();
        $paths = [];
        $batches->map(function (Batch $batch) {
            return $this->buildBatchConfig($batch);
        })
            ->each(function (array $path) use (&$paths) {
                $uri = key($path);
                $method = key($path[$uri]);

                if (array_key_exists($uri, $paths)) {
                    foreach ($path[$uri][$method]['responses'] as $responseCode => $response) {
                        $paths[$uri][$method]['responses'][$responseCode] = $response;
                    }

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
                $pathData->response['contentType'] => [
                    'schema' => $pathData->response['schema'],
                ],
            ];
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
}
