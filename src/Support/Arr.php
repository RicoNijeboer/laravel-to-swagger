<?php

namespace Rico\Swagger\Support;

use Illuminate\Support\Arr as IlluminateArr;

/**
 * Class Arr
 *
 * @package Rico\Swagger\Support
 */
class Arr extends IlluminateArr
{
    /**
     * @param array|null    $array $array
     * @param callable|null $callback
     *
     * @return bool
     */
    public static function some(?array $array, callable $callback): bool
    {
        if (method_exists(parent::class, 'some'))
        {
            return parent::some($array, $callback);
        }

        foreach ($array as $key => $value)
        {
            if ($callback($value, $key))
            {
                return true;
            }
        }

        return false;
    }
}