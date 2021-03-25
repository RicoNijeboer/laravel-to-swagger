<?php

namespace Rico\Swagger\Support;

use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use Rico\Swagger\Exceptions\UnsupportedFilterTypeException;

/**
 * Class RouteFilter
 *
 * @package Rico\Swagger\Support
 */
class RouteFilter extends Filter
{
    const FILTER_TYPE_ACTION = 'action';
    const FILTER_TYPE_URI = 'uri';
    const FILTER_TYPE_MIDDLEWARE = 'middleware';
    const FILTER_TYPES = [
        self::FILTER_TYPE_ACTION,
        self::FILTER_TYPE_URI,
        self::FILTER_TYPE_MIDDLEWARE,
    ];
    const FILTER_TYPE_ALIASES = [
        'a'        => self::FILTER_TYPE_ACTION,
        'e'        => self::FILTER_TYPE_URI,
        'm'        => self::FILTER_TYPE_MIDDLEWARE,
        'url'      => self::FILTER_TYPE_URI,
        'endpoint' => self::FILTER_TYPE_URI,
        'u'        => self::FILTER_TYPE_URI,
    ];

    /**
     * Extract Route filters from the given input.
     *
     * @param string $input
     *
     * @return array
     * @throws UnsupportedFilterTypeException
     */
    public static function extract(string $input): array
    {
        $filters = parent::extract($input);

        if (collect($filters)->every(fn (Filter $filter) => $filter->isType(self::FILTER_TYPES))) {
            return $filters;
        }

        throw new UnsupportedFilterTypeException('one of: ' . implode(', ', array_map(fn (RouteFilter $filter) => $filter->getType(), $filters)));
    }

    /**
     * Check if the route matches the filter.
     *
     * @param Route $route
     *
     * @return bool
     */
    public function matchesRoute(Route $route): bool
    {
        $filterable = $this->getFilterable()($route);

        return $this->arrayMatches($filterable);
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        $type = parent::getType();

        return self::FILTER_TYPE_ALIASES[$type] ?? $type;
    }

    /**
     * Get the filterable
     *
     * @return callable
     */
    protected function getFilterable(): callable
    {
        $type = $this->getType();

        switch ($type) {
            case self::FILTER_TYPE_URI   :
                return fn (Route $r) => [Str::start($r->uri(), '/')];
            case self::FILTER_TYPE_ACTION     :
                return fn (Route $r) => $r->getAction();
            case self::FILTER_TYPE_MIDDLEWARE :
                return fn (Route $r) => $r->gatherMiddleware();
            default:
                throw new UnsupportedFilterTypeException($type);
        }
    }
}