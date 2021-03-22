<?php

namespace Rico\Swagger\Exceptions;

use Exception;

/**
 * Class UnsupportedSwaggerExportTypeException
 *
 * @package Rico\Swagger\Exceptions
 */
class UnsupportedSwaggerExportTypeException extends Exception
{
    /**
     * UnsupportedSwaggerExportTypeException constructor.
     *
     * @param int $type
     */
    public function __construct(int $type)
    {
        parent::__construct(
            "The provided type [{$type}] can not be used to export a Swagger configuration.",
            500
        );
    }
}