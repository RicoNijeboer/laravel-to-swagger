<?php

namespace RicoNijeboer\Swagger\Tests\Unit\Support;

use RicoNijeboer\Swagger\Support\Concerns\HelperMethods;
use RicoNijeboer\Swagger\Tests\TestCase;

/**
 * Class HelperMethodsTest
 *
 * @package RicoNijeboer\Swagger\Tests\Unit\Support
 */
class HelperMethodsTest extends TestCase
{
    use HelperMethods;

    /**
     * @test
     */
    public function the_recursively_method_calls_with_all_possible_keys_in_dot_notation()
    {
        $array = [
            'id'   => 1,
            'name' => 'Rico',
            'job'  => [
                'title'    => 'Medior Software Engineer',
                'location' => [
                    'country' => 'Netherlands',
                ],
            ],
        ];
        $expectedKeys = [
            'id',
            'name',
            'job',
            'job.title',
            'job.location',
            'job.location.country',
        ];

        $actualKeys = [];

        $this->recursively($array, function ($_, $key) use (&$actualKeys) {
            $actualKeys[] = $key;
        });

        $this->assertSameSize($expectedKeys, $actualKeys);
        $this->assertEquals($expectedKeys, $actualKeys);
    }
}
