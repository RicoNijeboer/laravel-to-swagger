<?php

namespace RicoNijeboer\Swagger\Tests\Concerns;

use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use RicoNijeboer\Swagger\Support\Concerns\HelperMethods as SrcHelperMethods;

trait HelperMethods
{

    use SrcHelperMethods;

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
