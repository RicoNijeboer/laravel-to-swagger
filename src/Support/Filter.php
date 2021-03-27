<?php

namespace Rico\Swagger\Support;

use Illuminate\Support\Str;

/**
 * Class Filters
 *
 * @package Rico\Swagger\Support
 */
class Filter
{
    protected const TYPE_INDEX = 1;
    protected const FILTER_REGEX = <<<REGEXP
/([a-zA-Z]+):((['"]([\w\/*\-%&?:]*)['"])|([\w\/*\-%&?]*))/m
REGEXP;
    protected string $type;
    protected string $filter;

    /**
     * Filter constructor.
     *
     * @param string $type
     * @param string $filter
     */
    public function __construct(string $type, string $filter)
    {
        $this->type = $type;
        $this->filter = $filter;
    }

    /**
     * Extract Filters from the given input.
     *
     * @param string $input
     *
     * @return static[]
     */
    public static function extract(string $input): array
    {
        preg_match_all(static::FILTER_REGEX, $input, $matches, PREG_SET_ORDER, 0);

        return array_map(function (array $match) {
            $type = $match[static::TYPE_INDEX];
            $filter = array_pop($match);

            return new static($type, $filter);
        }, $matches);
    }

    /**
     * Check if the given value matches the filter.
     *
     * @param string $value
     *
     * @return bool
     */
    public function matches(string $value): bool
    {
        return Str::is($this->filter, $value);
    }

    /**
     * Check if the given array matches the filter.
     *
     * @param array $array
     *
     * @return bool
     */
    public function arrayMatches(array $array): bool
    {
        foreach ($array as $value) {
            $matches = is_array($value)
                ? $this->arrayMatches($value)
                : $this->matches($value);

            if ($matches) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check the type of the filter.
     *
     * @param string|string[] $type
     *
     * @return bool
     */
    public function isType($type): bool
    {
        return Str::is($type, $this->getType());
    }

    /**
     * Get the type of the filter.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the type of the filter.
     *
     * @return string
     */
    public function getFilter(): string
    {
        return $this->filter;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return "{$this->getType()}:'{$this->getFilter()}'";
    }
}