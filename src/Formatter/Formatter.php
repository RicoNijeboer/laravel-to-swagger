<?php

namespace Rico\Swagger\Formatter;

use SoapBox\Formatter\Formatter as BaseFormatter;

/**
 * Class Formatter
 *
 * @package Rico\Swagger\Formatter
 */
class Formatter
{

    /**
     * @param array $data
     *
     * @return string
     */
    public static function yaml(array $data): string
    {
        $yaml = BaseFormatter::make($data, BaseFormatter::ARR)->toYaml();

        $yaml = preg_replace('/^(\s*)-(\s?\n\s*)/m', '$1- ', $yaml);

        return str_replace("---\n", '', $yaml);
    }

    /**
     * @param array $data
     *
     * @return string
     */
    public static function json(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }
}