<?php

namespace Rico\Swagger\Swagger;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Rico\Reader\Endpoints\EndpointData;
use Rico\Swagger\Formatter\Formatter;

/**
 * Class SwaggerBuilder
 *
 * @package Rico\Swagger
 */
class Builder
{

    protected string $openapi = '3.0.0';

    protected array $info = [
        'title'       => ' ',
        'version'     => ' ',
        'description' => ' ',
    ];

    /** @var Server[] */
    protected array $servers = [];

    protected array $paths = [];

    /** @var Tag[] */
    protected array $tags;

    /**
     * SwaggerBuilder constructor.
     *
     * @param string|null $title
     * @param string|null $description
     * @param string|null $version
     * @param Tag[]|null  $tags
     */
    public function __construct(?string $title = null, ?string $description = null, ?string $version = null, ?array $tags = [])
    {
        $this->title($title ?? ' ');
        $this->version($version ?? 'v0.0.1');
        $this->description($description ?? ' ');
        $this->tags($tags ?? []);
    }

    /**
     * Set the title of the Swagger document.
     *
     * @param string $title
     *
     * @return $this
     */
    public function title(string $title): self
    {
        $this->info['title'] = $title;

        return $this;
    }

    /**
     * Set the description of the Swagger document.
     *
     * @param string $description
     *
     * @return $this
     */
    public function description(string $description): self
    {
        $this->info['description'] = $description;

        return $this;
    }

    /**
     * Set the description of the Swagger document.
     *
     * @param Tag[] $tags
     *
     * @return $this
     */
    public function tags(array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Set the version of the Swagger document.
     *
     * @param string $version
     *
     * @return $this
     */
    public function version(string $version): self
    {
        $this->info['version'] = $version;

        return $this;
    }

    /**
     * Adds a server to the Swagger documentation.
     *
     * @param Server $server
     *
     * @return $this
     */
    public function addServer(Server $server): self
    {
        $this->servers[] = $server;

        return $this;
    }

    /**
     * Adds an endpoint based on the given endpoint data.
     *
     * @param string         $uri
     * @param EndpointData[] $data
     *
     * @return Builder
     */
    public function addPath(string $uri, array $data): self
    {
        $uri = Str::start($uri, '/');

        if ( ! array_key_exists($uri, $this->paths))
        {
            $this->paths[$uri] = collect();
        }

        $endpoints = array_map(fn(EndpointData $endpointData) => new Endpoint($endpointData), $data);

        foreach ($endpoints as $method => $endpoint)
        {
            $this->paths[$uri]->put($method, $endpoint);
        }

        return $this;
    }

    /**
     * Convert the Swagger documentation to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $paths = array_map(function (Collection $endpoints) {
            return $endpoints
                ->map(function (Endpoint $endpoint) {
                    $endpoint->applyTags($this->tags);

                    return $endpoint->toArray();
                })
                ->all();
        }, $this->paths);

        return array_filter([
            'info'    => $this->info,
            'servers' => array_map(fn(Server $server) => $server->toArray(), $this->servers),
            'openapi' => $this->openapi,
            'paths'   => $paths,
        ], fn($item) => ! empty($item));
    }

    /**
     * Create the YAML content.
     *
     * @return string
     */
    public function toYaml(): string
    {
        return Formatter::yaml($this->toArray());
    }

    /**
     * Create the JSON content.
     *
     * @return string
     */
    public function toJson(): string
    {
        return Formatter::json($this->toArray());
    }
}