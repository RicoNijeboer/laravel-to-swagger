<?php

namespace Rico\Swagger\Swagger;

use Illuminate\Support\Str;

/**
 * Class DataType
 *
 * @package Rico\Swagger\Swagger
 */
class Property
{

    /**
     * @param string $type
     * @param array  $extraInfo
     *
     * @return string[]
     */
    public static function makeData(string $type, array $value, array $extraInfo): array
    {
        $method = Str::camel('make_' . $type . '_data');

        $swaggerInfo = method_exists(static::class, $method)
            ? static::$method($value, $extraInfo)
            : ['type' => $type];

        if (array_key_exists('nullable', $extraInfo) && $extraInfo['nullable'] === true)
        {
            $swaggerInfo['nullable'] = true;
        }

        return $swaggerInfo;
    }

    public static function makeStringData(array $value, array $extraInfo): array
    {
        $swaggerInfo = ['type' => 'string'];

        if ($extraInfo['isEmail'] ?? false)
        {
            $swaggerInfo['format'] = 'email';
        }

        if (array_key_exists('min', $extraInfo))
        {
            $swaggerInfo['minLength'] = $extraInfo['min'];
        }

        if (array_key_exists('max', $extraInfo))
        {
            $swaggerInfo['maxLength'] = $extraInfo['max'];
        }

        return $swaggerInfo;
    }

    public static function makeIntegerData(array $value, array $extraInfo): array
    {
        $swaggerInfo = ['type' => 'integer'];

        if (array_key_exists('min', $extraInfo))
        {
            $swaggerInfo['minimum'] = $extraInfo['min'];
        }

        if (array_key_exists('max', $extraInfo))
        {
            $swaggerInfo['maximum'] = $extraInfo['max'];
        }

        return $swaggerInfo;
    }
}