<?php

namespace RicoNijeboer\Swagger\Support;

use Illuminate\Support\Arr;

/**
 * Class ValueHelpers
 *
 * @package RicoNijeboer\Swagger\Support
 */
class ValueHelper
{
    /**
     * @param mixed $value
     *
     * @return array
     */
    public static function jsonResponseProperty($value): array
    {
        if (is_null($value)) {
            return [
                'nullable' => true,
            ];
        }

        if (is_array($value)) {
            if (Arr::isAssoc($value)) {
                return [
                    'type'       => 'object',
                    'properties' => collect($value)
                        ->mapWithKeys(function ($responseItem, string $responseProperty) {
                            return [
                                $responseProperty => static::jsonResponseProperty($responseItem),
                            ];
                        })
                        ->toArray(),
                ];
            }

            return [
                'type'  => 'array',
                'items' => static::jsonResponseProperty($value[key($value)]),
            ];
        }

        if (is_int($value)) {
            return [
                'type' => 'integer',
            ];
        }

        if (is_numeric($value)) {
            return [
                'type' => 'number',
            ];
        }

        if (is_string($value)) {
            if (preg_match('/([a-zA-Z0-9]+)@([a-zA-Z0-9]+)\.([a-z]+)/', $value)) {
                return [
                    'type'   => 'string',
                    'format' => 'email',
                ];
            }

            return [
                'type' => 'string',
            ];
        }

        dd($value);
        ray()->pause();
    }
}
