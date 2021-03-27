<?php

use Illuminate\Routing\Route;
use Mockery\MockInterface;
use Pest\Expectations\Expectation;
use PHPUnit\Framework\Assert;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toHaveMethod', function (string $method) {
    /** @var Expectation $this */
    $this->toBeObject();

    Assert::assertTrue(method_exists($this->value, $method));

    return $this;
});

expect()->extend('toHavePublicMethod', function (string $method) {
    /** @var Expectation $this */
    $this->toHaveMethod($method);

    $reflectedMethod = method($this->value, $method);

    Assert::assertTrue($reflectedMethod->isPublic());

    return $this;
});

expect()->extend('toHaveProtectedMethod', function (string $method) {
    /** @var Expectation $this */
    $this->toHaveMethod($method);

    $reflectedMethod = method($this->value, $method);

    Assert::assertTrue($reflectedMethod->isProtected());

    return $this;
});

expect()->extend('toHavePrivateMethod', function (string $method) {
    /** @var Expectation $this */
    $this->toHaveMethod($method);

    $reflectedMethod = method($this->value, $method);

    Assert::assertTrue($reflectedMethod->isPrivate());

    return $this;
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function mock(string $abstract, Closure $mock = null)
{
    return Mockery::mock($abstract, $mock ?? fn ($mock) => $mock);
}

function method($value, string $method, bool $setAccessible = false): ReflectionMethod
{
    $reflection = new ReflectionMethod($value, $method);

    if ($setAccessible) {
        $reflection->setAccessible(true);
    }

    return $reflection;
}

function property($value, string $property, bool $setAccessible = false): ReflectionProperty
{
    $reflection = new ReflectionProperty($value, $property);

    if ($setAccessible) {
        $reflection->setAccessible(true);
    }

    return $reflection;
}

/**
 * @param string|null $uri
 * @param array|null  $middleware
 * @param array|null  $action
 *
 * @return MockInterface|Route
 */
function mockRoute(?string $uri = null, ?array $middleware = null, ?array $action = null, array $parameters = null)
{
    return mock(Route::class, function (MockInterface $route) use ($uri, $middleware, $action, $parameters) {
        $route->shouldReceive('gatherMiddleware')
            ->andReturn($middleware ?? [
                'api',
                'throttle:api',
                'auth:api',
            ]);
        $route->shouldReceive('uri')
            ->andReturn($uri ?? 'order');
        $route->shouldReceive('parameters')
            ->andReturn($parameters ?? []);
        $route->shouldReceive('getAction')
            ->andReturn($action ?? [
                'controller' => 'You\\Are\\Awesome\\Thank\\You',
                'uses'       => 'You\\Are\\Awesome\\Thank\\You@method',
            ]);
    });
}