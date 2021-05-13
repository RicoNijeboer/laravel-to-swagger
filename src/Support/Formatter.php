<?php

namespace RicoNijeboer\Swagger\Support;

use SoapBox\Formatter\Formatter as BaseFormatter;

/**
 * Class Formatter
 *
 * @package RicoNijeboer\Swagger\Support
 */
class Formatter
{
    /**
     * @param array $array
     *
     * @return string
     */
    public static function toYaml(array $array): string
    {
        $yaml = BaseFormatter::make($array, BaseFormatter::ARR)->toYaml();

        $yaml = preg_replace('/^(\s*)-(\s?\n\s*)/m', '$1- ', $yaml);
        $yaml = preg_replace('/(:)\s>\s*(\w+)/m', '$1 $2', $yaml);

        return str_replace("---\n", '', $yaml);
    }
}
