<?php

namespace Rico\Swagger\Swagger;

use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Rico\Swagger\Support\Filter;
use Rico\Swagger\Support\RouteFilter;

/**
 * Class Tag
 *
 * @package Rico\Swagger\Swagger
 */
class Tag
{
    private string $tag;
    /** @var RouteFilter[] */
    private array $filters = [];

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
     * Add a filter to the Tag.
     *
     * @param RouteFilter $filter
     *
     * @return $this
     */
    public function addFilter(RouteFilter $filter): self
    {
        if (!$filter->isType([])) {
        }

        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Check if the endpoint matches the given filters.
     *
     * @param Route $route
     *
     * @return bool
     */
    public function matches(Route $route): bool
    {
        return collect($this->filters)
            ->groupBy(fn (RouteFilter $filter) => $filter->getType())
            ->every(function (Collection $filters) use ($route) {
                /** @var RouteFilter $filter */
                $filter = $filters->some;

                return $filter->matchesRoute($route);
            });
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->tag;
    }

    /**
     * Get the filterable properties of an endpoint.
     *
     * @param Route $route
     *
     * @return array
     */
    protected function getFilterable(Route $route): array
    {
        return [
            self::FILTER_TYPE_ENDPOINT   => [Str::start($route->uri(), '/')],
            self::FILTER_TYPE_ACTION     => $route->getAction(),
            self::FILTER_TYPE_MIDDLEWARE => $route->gatherMiddleware(),
        ];
    }
}