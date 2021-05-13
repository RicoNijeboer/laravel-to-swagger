<?php

namespace RicoNijeboer\Swagger\Support\Concerns;

use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

trait HelperMethods
{
    /**
     * @param array    $array
     * @param callable $callback
     * @param string   $keyPrefix
     */
    protected function recursively(array $array, callable $callback, string $keyPrefix = ''): void
    {
        foreach ($array as $key => $item) {
            $keyWithPrefix = implode('.', array_filter([$keyPrefix, $key]));

            $callback($item, $keyWithPrefix);

            if (is_array($item)) {
                $this->recursively($item, $callback, $keyWithPrefix);
            }
        }
    }

    /**
     * @param string $date
     *
     * @return string|false
     */
    protected function getDateFormat(string $date)
    {
        if (strtotime($date) === false) {
            return false;
        }

        // check Day -> (0[1-9]|[1-2][0-9]|3[0-1])
        // check Month -> (0[1-9]|1[0-2])
        // check Year -> [0-9]{4} or \d{4}
        $patterns = [
            '/\b\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.\d{3,8}Z\b/'     => 'Y-m-d\TH:i:s.u\Z', // format DATE ISO 8601
            '/\b\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])\b/' => 'Y-m-d',
            '/\b\d{4}-(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])\b/' => 'Y-d-m',
            '/\b(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-\d{4}\b/' => 'd-m-Y',
            '/\b(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])-\d{4}\b/' => 'm-d-Y',

            '/\b\d{4}\/(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\b/' => 'Y/d/m',
            '/\b\d{4}\/(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\b/' => 'Y/m/d',
            '/\b(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/\d{4}\b/' => 'd/m/Y',
            '/\b(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/\d{4}\b/' => 'm/d/Y',

            '/\b\d{4}\.(0[1-9]|1[0-2])\.(0[1-9]|[1-2][0-9]|3[0-1])\b/'    => 'Y.m.d',
            '/\b\d{4}\.(0[1-9]|[1-2][0-9]|3[0-1])\.(0[1-9]|1[0-2])\b/'    => 'Y.d.m',
            '/\b(0[1-9]|[1-2][0-9]|3[0-1])\.(0[1-9]|1[0-2])\.\d{4}\b/'    => 'd.m.Y',
            '/\b(0[1-9]|1[0-2])\.(0[1-9]|[1-2][0-9]|3[0-1])\.\d{4}\b/'    => 'm.d.Y',

            // for 24-hour | hours seconds
            '/\b(?:2[0-3]|[01][0-9]):[0-5][0-9](:[0-5][0-9])\.\d{3,6}\b/' => 'H:i:s.u',
            '/\b(?:2[0-3]|[01][0-9]):[0-5][0-9](:[0-5][0-9])\b/'          => 'H:i:s',
            '/\b(?:2[0-3]|[01][0-9]):[0-5][0-9]\b/'                       => 'H:i',

            // for 12-hour | hours seconds
            '/\b(?:1[012]|0[0-9]):[0-5][0-9](:[0-5][0-9])\.\d{3,6}\b/'    => 'h:i:s.u',
            '/\b(?:1[012]|0[0-9]):[0-5][0-9](:[0-5][0-9])\b/'             => 'h:i:s',
            '/\b(?:1[012]|0[0-9]):[0-5][0-9]\b/'                          => 'h:i',

            '/\.\d{3}\b/' => '.v',
        ];
        $date = preg_replace('/\b\d{2}:\d{2}\b/', 'H:i', $date);
        $date = preg_replace(array_keys($patterns), array_values($patterns), $date);

        return preg_match('/\d/', $date) ? false : $date;
    }

    /**
     * @param string|object $on
     * @param string|null   $method
     *
     * @return ReflectionMethod
     * @throws ReflectionException
     */
    protected function method($on, ?string $method = null): ReflectionMethod
    {
        $method = new ReflectionMethod($on, $method);

        $method->setAccessible(true);

        return $method;
    }

    /**
     * @param string|object $on
     * @param string|null   $property
     *
     * @return ReflectionProperty
     * @throws ReflectionException
     */
    protected function property($on, ?string $property = null): ReflectionProperty
    {
        $property = new ReflectionProperty($on, $property);

        $property->setAccessible(true);

        return $property;
    }
}
