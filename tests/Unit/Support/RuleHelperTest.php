<?php

namespace RicoNijeboer\Swagger\Tests\Unit\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RicoNijeboer\Swagger\Support\RuleHelper;
use RicoNijeboer\Swagger\Tests\TestCase;

/**
 * Class RuleHelperTest
 *
 * @package RicoNijeboer\Swagger\Tests\Unit\Support
 */
class RuleHelperTest extends TestCase
{
    /**
     * @test
     * @throws ValidationException
     */
    public function it_adds_a_format_rule_to_strings_that_have_a_regex_rule()
    {
        $rules = [
            'required',
            'string',
            'regex:/[0-9]+\_[a-z]/',
        ];

        // Ensure rules used actually work in Laravel (It will throw a validation exception if it does not).
        Validator::make(['field' => '012_a'], ['field' => $rules])->validate();

        $result = RuleHelper::openApiProperty('field', $rules, []);

        $this->assertArrayHasKeys(['format' => '[0-9]+\_[a-z]'], $result);
    }
}
