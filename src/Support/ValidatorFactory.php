<?php

namespace RicoNijeboer\Swagger\Support;

use Illuminate\Validation\Factory;

/**
 * Class ValidatorFactory
 *
 * @package RicoNijeboer\Swagger\Support
 */
class ValidatorFactory extends Factory
{
    protected static array $onValidate = [];

    public function onValidate(callable $onValidate): void
    {
        static::$onValidate[] = $onValidate;
    }

    public function make(array $data, array $rules, array $messages = [], array $customAttributes = [])
    {
        foreach (static::$onValidate as $onValidate) {
            $onValidate($rules, $data);
        }

        return parent::make($data, $rules, $messages, $customAttributes);
    }
}
