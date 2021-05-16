<?php

namespace RicoNijeboer\Swagger\Exceptions;

use Exception;
use Illuminate\Support\MessageBag;

/**
 * Class MalformedServersException
 *
 * @package RicoNijeboer\Swagger\Exceptions
 */
class MalformedServersException extends Exception
{
    public function __construct(MessageBag $errors)
    {
        ray($errors);
        parent::__construct();
    }
}
