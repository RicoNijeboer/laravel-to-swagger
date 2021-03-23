<?php

namespace Rico\Swagger\Swagger;

use Illuminate\Support\Str;
use Rico\Swagger\Support\Arr;

/**
 * Class Tag
 *
 * @package Rico\Swagger\Swagger
 */
class Tag
{

    private string $tag;

    private array $filters = [
        'endpoints'  => [],
        'middleware' => [],
        'controller' => [],
    ];

    /**
     * Tag constructor.
     *
     * @param string $tag
     */
    public function __construct(string $tag)
    {
        $this->tag = $tag;
    }

    /**
     * @param string $tag
     *
     * @return static
     */
    public static function new(string $tag): self
    {
        return new static($tag);
    }

    /**
     * Adds an controller filter for the tag.
     * (Example: "*Controller" would catch both all controllers and "Product*" would catch all controllers starting with "Product")
     *
     * @param string $controller
     *
     * @return $this
     */
    public function addControllerFilter(string $controller): self
    {
        $this->filters['controller'][] = $controller;

        return $this;
    }

    /**
     * Adds an endpoint filter for the tag.
     * (Example: "/products*" would catch both "/products" and "/products/{product}")
     *
     * @param string $endpoint
     *
     * @return $this
     */
    public function addEndpointFilter(string $endpoint): self
    {
        $this->filters['endpoints'][] = $endpoint;

        return $this;
    }

    /**
     * Adds an middleware filter for the tag.
     * (Example: "web*" would catch both "web" and "website")
     *
     * @param string $middleware
     *
     * @return $this
     */
    public function addMiddlewareFilter(string $middleware): self
    {
        $this->filters['middleware'][] = $middleware;

        return $this;
    }

    /**
     * @param Endpoint $endpoint
     *
     * @return bool
     */
    public function matches(Endpoint $endpoint): bool
    {
        return $this->matchesEndpointFilter($endpoint)
            && $this->matchesControllerFilter($endpoint)
            && $this->matchesMiddlewareFilter($endpoint);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->tag;
    }

    /**
     * @param Endpoint $endpoint
     *
     * @return bool
     */
    protected function matchesEndpointFilter(Endpoint $endpoint): bool
    {
        if (empty($this->filters['endpoints']))
        {
            return true;
        }

        return Str::is($this->filters['endpoints'], $endpoint->uri());
    }

    /**
     * @param Endpoint $endpoint
     *
     * @return bool
     */
    protected function matchesControllerFilter(Endpoint $endpoint): bool
    {
        if (empty($this->filters['controller']))
        {
            return true;
        }

        return Str::is($this->filters['controller'], $endpoint->controller());
    }

    /**
     * @param Endpoint $endpoint
     *
     * @return bool
     */
    protected function matchesMiddlewareFilter(Endpoint $endpoint): bool
    {
        if (empty($this->filters['middleware']))
        {
            return true;
        }

        return Arr::some($endpoint->middlewares(), fn(string $middleware) => Str::is($this->filters['middleware'], $middleware));
    }
}