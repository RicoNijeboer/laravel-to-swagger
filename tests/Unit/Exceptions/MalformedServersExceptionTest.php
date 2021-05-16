<?php

namespace RicoNijeboer\Swagger\Tests\Unit\Exceptions;

use Illuminate\Support\MessageBag;
use RicoNijeboer\Swagger\Exceptions\MalformedServersException;
use RicoNijeboer\Swagger\Tests\TestCase;

/**
 * Class MalformedServersExceptionTest
 *
 * @package RicoNijeboer\Swagger\Tests\Unit\Exceptions
 */
class MalformedServersExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_formats_the_message_nicely()
    {
        $messageBag = new MessageBag([
            'servers.0.url'                  => ['The servers.0.url is required.'],
            'servers.0.variables.customerId' => ['The servers.0.variables.customerId must be an array.'],
            'servers.0.variables.port'       => ['The servers.0.variables.port must be an array.'],
        ]);

        $exception = new MalformedServersException($messageBag);

        $message = $exception->getMessage();

        $this->assertStringStartsWith('Whoops. Looks like something went wrong while reading your Swagger servers configuration', $message);
        $this->assertStringContainsString('The [url] is required.', $message);
        $this->assertStringContainsString('The variable [port] must be an array.', $message);
        $this->assertStringContainsString('The variable [customerId] must be an array.', $message);
    }

    /**
     * @test
     */
    public function it_groups_the_errors_per_server()
    {
        $messageBag = new MessageBag([
            'servers.0.url' => ['The servers.0.url is required.'],
            'servers.1.url' => ['The servers.1.url is required.'],
            'servers.2.url' => ['The servers.2.url is required.'],
            'servers.3.url' => ['The servers.3.url is required.'],
        ]);

        $exception = new MalformedServersException($messageBag);

        $message = $exception->getMessage();

        $this->assertStringContainsString('Validation failed with errors for the 1st server', $message);
        $this->assertStringContainsString('Validation failed with errors for the 2nd server', $message);
        $this->assertStringContainsString('Validation failed with errors for the 3rd server', $message);
        $this->assertStringContainsString('Validation failed with errors for the 4th server', $message);
    }
}
