<?php

namespace Rico\Swagger\Exceptions;

use Exception;

/**
 * Class UnsupportedFilterTypeException
 *
 * @package Rico\Swagger\Exceptions
 */
class UnsupportedFilterTypeException extends Exception
{

    public function __construct(string $type)
    {
        parent::__construct(
            "You are using an unsupported filter type: [{$type}]. Please check the supported types in our documentation."
        );
    }
}