<?php

namespace RicoNijeboer\Swagger\Support\Concerns;

use Generator;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

trait HelperMethods
{
    /**
     * @param array  $array
     * @param string $keyPrefix
     *
     * @return Generator
     */
    protected function recursively(array $array, string $keyPrefix = ''): Generator
    {
        foreach ($array as $key => $item) {
            $keyWithPrefix = implode('.', array_filter([$keyPrefix, $key], fn (string $k) => strlen($k) > 0));

            if (is_array($item)) {
                foreach ($this->recursively($item, $keyWithPrefix) as [$subItem, $subItemKey]) {
                    yield [$subItem, $subItemKey];
                }
                continue;
            }

            yield [$item, $keyWithPrefix];
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
        $date = preg_replace(array_keys($patterns), array_values($patterns), $date);
        ray($date);

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

    /**
     * Get an ordinal number from the integer.
     *   1 => '1st'
     *   2 => '2nd'
     *   etc.
     *
     * @param int $number
     *
     * @return string
     */
    protected function ordinal(int $number): string
    {
        if (!in_array($number % 100, [11, 12, 13])) {
            switch ($number % 10) {
                case 1:
                    return $number . 'st';
                case 2:
                    return $number . 'nd';
                case 3:
                    return $number . 'rd';
            }
        }

        return $number . 'th';
    }
}
