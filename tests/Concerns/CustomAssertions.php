<?php

namespace RicoNijeboer\Swagger\Tests\Concerns;

use Illuminate\Support\Arr;
use RicoNijeboer\Swagger\Tests\TestCase;

/**
 * Trait CustomAssertions
 *
 * @package RicoNijeboer\Swagger\Tests\Concerns
 * @mixin TestCase
 */
trait CustomAssertions
{
    /**
     * @param string[] $keys
     * @param mixed    $array
     */
    protected function assertArrayHasKeys(array $keys, array $array)
    {
        foreach ($keys as $_ => $key) {
            if (is_string($_)) {
                $this->assertTrue(
                    Arr::has($array, $_),
                    "Failed asserting that array has key [{$_}]"
                );
                $this->assertEquals(
                    $key,
                    Arr::get($array, $_),
                    "Failed asserting that the key [{$_}] of the array has value [{$key}]"
                );

                continue;
            }

            $this->assertTrue(
                Arr::has($array, $key),
                "Failed asserting that array has key [{$key}]"
            );
        }
    }

    /**
     * @param string[] $keys
     * @param mixed    $array
     */
    protected function assertArrayDoesntHaveKeys(array $keys, array $array)
    {
        foreach ($keys as $_ => $key) {
            if (is_string($_)) {
                $has = Arr::has($array, $_);

                $this->assertFalse(
                    $has,
                    "Failed asserting that array doesn't have key [{$_}]"
                );
                $this->assertNotEquals(
                    $key,
                    Arr::get($array, $_),
                    "Failed asserting that the key [{$_}] of the array doesn't have value [{$key}]"
                );

                continue;
            }

            $this->assertFalse(
                Arr::has($array, $key),
                "Failed asserting that array doesn't have key [{$key}]"
            );
        }
    }

    /**
     * @param array $values
     * @param array $array
     */
    protected function assertArrayHasValues(array $values, array $array)
    {
        foreach ($values as $value) {
            $this->assertTrue(
                in_array($value, $array),
                "Failed asserting that the array has value [{$value}]"
            );
        }
    }
}
