<?php

namespace RicoNijeboer\Swagger\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Class RuleHelper
 *
 * @package RicoNijeboer\Swagger\Support
 */
class RuleHelper
{
    public const DATA_TYPE_STRING = 1;
    public const DATA_TYPE_INT = 2;
    public const DATA_TYPE_FLOAT = 3;
    public const DATA_TYPE_DATETIME = 4;
    public const DATA_TYPE_FORMATTED_DATETIME = 5;
    public const DATA_TYPE_BOOLEAN = 6;
    public const DATA_TYPE_ARRAY = 7;
    public const DATA_TYPE_OBJECT = 8;
    public const DATA_TYPE_DOUBLE = 9;
    public const DATA_TYPE_PASSWORD = 10;
    public const DATA_TYPES = [
        self::DATA_TYPE_STRING             => 'string',
        self::DATA_TYPE_INT                => 'integer',
        self::DATA_TYPE_FLOAT              => 'number',
        self::DATA_TYPE_DATETIME           => 'string',
        self::DATA_TYPE_FORMATTED_DATETIME => 'string',
        self::DATA_TYPE_BOOLEAN            => 'boolean',
        self::DATA_TYPE_ARRAY              => 'array',
        self::DATA_TYPE_OBJECT             => 'object',
        self::DATA_TYPE_DOUBLE             => 'number',
        self::DATA_TYPE_PASSWORD           => 'string',
    ];

    public static function hasRule(string $pattern, array $rules): bool
    {
        if (!Str::contains($pattern, '*')) {
            return in_array($pattern, $rules);
        }

        foreach ($rules as $rule) {
            if (is_string($pattern) && Str::is($pattern, $rule)) {
                return true;
            }
        }

        return false;
    }

    public static function getRule(string $pattern, array $rules): ?string
    {
        foreach ($rules as $rule) {
            if (is_string($pattern) && Str::is($pattern, $rule)) {
                return $rule;
            }
        }

        return null;
    }

    public static function isNullable(array $rules): bool
    {
        return static::hasRule('nullable', $rules)
            || (
                !static::hasRule('required*', $rules)
                && !static::hasRule('exclude*', $rules)
            );
    }

    public static function isEmail(array $rules): bool
    {
        return static::hasRule('email*', $rules);
    }

    public static function isArray(array $rules): bool
    {
        return static::hasRule('array', $rules);
    }

    public static function isString(array $rules): bool
    {
        return static::dataType($rules) === static::DATA_TYPE_STRING;
    }

    public static function isRequired(array $rules): bool
    {
        return !static::isNullable($rules);
    }

    public static function dataType(array $rules): int
    {
        switch (true) {
            case static::hasRule('int', $rules):
            case static::hasRule('integer', $rules):
            case static::hasRule('digits:0', $rules):
                return static::DATA_TYPE_INT;

            case static::hasRule('digits:2', $rules):
                return static::DATA_TYPE_DOUBLE;

            case static::hasRule('numeric', $rules):
                return static::DATA_TYPE_FLOAT;

            case static::hasRule('date', $rules):
                return static::DATA_TYPE_DATETIME;

            case static::hasRule('date_format:*', $rules):
                return static::DATA_TYPE_FORMATTED_DATETIME;

            case static::hasRule('bool', $rules):
            case static::hasRule('boolean', $rules):
                return static::DATA_TYPE_BOOLEAN;

            case static::hasRule('array', $rules):
                return static::DATA_TYPE_ARRAY;

            case static::hasRule('json', $rules):
                return static::DATA_TYPE_OBJECT;

            case static::hasRule('password', $rules):
                return static::DATA_TYPE_PASSWORD;

            case static::hasRule('string', $rules):
            default:
                return static::DATA_TYPE_STRING;
        }
    }

    /**
     * @param int $dataType
     *
     * @return string
     */
    public static function dataTypeString(int $dataType): string
    {
        return static::DATA_TYPES[$dataType];
    }

    /**
     * @param array $rules
     *
     * @return float|int|null
     */
    public static function min(array $rules)
    {
        $valFunc = (static::isArray($rules) || static::isString($rules)) ? 'intval' : 'floatval';

        foreach ($rules as $rule) {
            $ruleArray = Arr::wrap($rule);

            foreach ($ruleArray as $ruleItem) {
                if (Str::startsWith($ruleItem, 'min:')) {
                    return $valFunc(explode(':', $ruleItem, 2)[1]);
                }
            }
        }

        return null;
    }

    /**
     * @param array $rules
     *
     * @return float|int|null
     */
    public static function max(array $rules)
    {
        $valFunc = (static::isArray($rules) || static::isString($rules)) ? 'intval' : 'floatval';

        foreach ($rules as $rule) {
            $ruleArray = Arr::wrap($rule);

            foreach ($ruleArray as $ruleItem) {
                if (Str::startsWith($ruleItem, 'max:')) {
                    return $valFunc(explode(':', $ruleItem, 2)[1]);
                }
            }
        }

        return null;
    }

    public static function openApiProperty(string $property, array $rules, array $ruleCache): array
    {
        if (!Arr::isAssoc($rules)) {
            $dataTypeInt = RuleHelper::dataType($rules);

            return array_filter([
                'type'     => RuleHelper::dataTypeString($dataTypeInt),
                'nullable' => RuleHelper::isNullable($rules),
                'minimum'  => RuleHelper::min($rules),
                'maximum'  => RuleHelper::max($rules),
                'format'   => (function (array $rules) {
                    if (static::isEmail($rules)) {
                        return 'email';
                    }

                    $regex = static::getRegex($rules);

                    if (!empty($regex)) {
                        return $regex;
                    }

                    return null;
                })($rules),
            ]);
        }

        $rulesHasStar = !empty(Arr::where(array_keys($rules), fn (string $key) => $key === '*'));

        if ($rulesHasStar || in_array('array', $rules)) {
            $childRules = $rules['*'] ?? [];

            if (array_key_exists('*', $rules)) {
                unset($rules['*']);
            }

            return array_merge(
                [
                    'type'  => 'array',
                    'items' => (function (array $rules, array $ruleCache) {
                        if (!Arr::isAssoc($rules['*'])) {
                            return static::openApiProperty('', $rules['*'], $ruleCache);
                        }

                        return [
                            'type'       => 'object',
                            'properties' => collect($rules['*'])
                                ->map(fn (array $r, string $key) => static::openApiProperty($key, $r, $ruleCache))
                                ->toArray(),
                        ];
                    })(['*' => $childRules], $ruleCache),
                ],
                array_filter([
                    'minItems' => static::min($rules),
                    'maxItems' => static::max($rules),
                ])
            );
        }

        return [
            'type'       => 'object',
            'properties' => collect($rules)
                ->map(fn (array $r, string $key) => static::openApiProperty($key, $r, $ruleCache))
                ->toArray(),
        ];
    }

    public static function getRegex(array $rules): ?string
    {
        $regexRule = self::getRule('regex:*', $rules);

        if (empty($regexRule)) {
            return null;
        }

        $expression = explode(':', $regexRule, 2)[1];
        $regexChar = substr($expression, 0, 1);
        $expression = substr($expression, 1);

        $lastPosition = strpos($expression, $regexChar, -1);

        return str_split($expression, $lastPosition)[0];
    }
}
