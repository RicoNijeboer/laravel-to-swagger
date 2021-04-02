<?php

use Illuminate\Routing\Route;
use Mockery\MockInterface;
use Rico\Swagger\Exceptions\UnsupportedFilterTypeException;
use Rico\Swagger\Support\RouteFilter;
use Rico\Swagger\Tests\TestCase;

it('will throw an error when the type does not exist and you use the constructor', function (string $notExistingType) {
    new RouteFilter($notExistingType, '*');
})->with([
    'lorem',
    'ipsum',
])->throws(UnsupportedFilterTypeException::class);

it('will throw an error when the type does not exist and you try to extract', function (string $notExistingType) {
    RouteFilter::extract("{$notExistingType}:*");
})->with([
    'lorem',
    'ipsum',
])->throws(UnsupportedFilterTypeException::class);

it('will not throw an error when you use a possible filter type', function (string $type) {
    expect(RouteFilter::extract("{$type}:*"))->toBeArray()->toHaveCount(1);
})->with(RouteFilter::FILTER_TYPES);

it('it returns the full name when you try to retrieve the type rather than the short hand', function (string $alias, string $type) {
    /** @var RouteFilter $filter */
    [$filter] = RouteFilter::extract("{$alias}:*");

    expect($filter->getType())->toBe($type);
})->with(function () {
    foreach (RouteFilter::FILTER_TYPE_ALIASES as $alias => $type) {
        yield [$alias, $type];
    }
});

it('will retrieve the url from the route when the type is URI', function () {
    $filter = new RouteFilter(RouteFilter::FILTER_TYPE_URI, '*');
    $getFilterable = method($filter, 'getFilterable', true);

    $route = mock(Route::class);
    $route->shouldReceive('uri')
        ->once()
        ->andReturn('order');

    $filterable = $getFilterable->invoke($filter);

    expect($filterable)
        ->toBeCallable()
        ->and($filterable($route))
        ->toBeArray()
        ->toHaveCount(1)
        ->toContain('/order');
});

it('will retrieve the action from the route when the type is ACTION', function () {
    $filter = new RouteFilter(RouteFilter::FILTER_TYPE_ACTION, '*');
    $getFilterable = method($filter, 'getFilterable', true);

    $route = mock(Route::class);
    $route->shouldReceive('getAction')
        ->once()
        ->andReturn([
            'controller' => 'You\\Are\\Awesome\\Thank\\You',
            'uses'       => 'You\\Are\\Awesome\\Thank\\You@method',
        ]);

    $filterable = $getFilterable->invoke($filter);

    expect($filterable)
        ->toBeCallable()
        ->and($filterable($route))
        ->toBeArray()
        ->toHaveCount(2)
        ->toHaveKey('controller', 'You\\Are\\Awesome\\Thank\\You')
        ->toHaveKey('uses', 'You\\Are\\Awesome\\Thank\\You@method');
});

it('will retrieve all middleware from the route when the type is MIDDLEWARE', function () {
    $filter = new RouteFilter(RouteFilter::FILTER_TYPE_MIDDLEWARE, '*');
    $getFilterable = method($filter, 'getFilterable', true);

    $route = mock(Route::class);
    $route->shouldReceive('gatherMiddleware')
        ->once()
        ->andReturn([
            'api',
            'throttle:api',
            'auth:api',
        ]);

    $filterable = $getFilterable->invoke($filter);

    expect($filterable)
        ->toBeCallable()
        ->and($filterable($route))
        ->toBeArray()
        ->toHaveCount(3)
        ->toContain('api')
        ->toContain('throttle:api')
        ->toContain('auth:api');
});

it('can see if the route matches the required filter', function (Route $route, RouteFilter $filter, bool $expected) {
    expect($filter->matchesRoute($route))->toBe($expected);
})->with(function () {
    $route = mock(Route::class, function (MockInterface $route) {
        $route->shouldReceive('gatherMiddleware')
            ->andReturn([
                'api',
                'throttle:api',
                'auth:api',
            ]);
        $route->shouldReceive('uri')
            ->andReturn('order');
        $route->shouldReceive('getAction')
            ->andReturn([
                'controller' => 'You\\Are\\Awesome\\Thank\\You',
                'uses'       => 'You\\Are\\Awesome\\Thank\\You@method',
            ]);
    });
    yield [
        $route,
        new RouteFilter(RouteFilter::FILTER_TYPE_URI, '*order*'),
        true,
    ];
    yield [
        $route,
        new RouteFilter(RouteFilter::FILTER_TYPE_URI, '*other*'),
        false,
    ];
    yield [
        $route,
        new RouteFilter(RouteFilter::FILTER_TYPE_ACTION, '*Awesome*'),
        true,
    ];
    yield [
        $route,
        new RouteFilter(RouteFilter::FILTER_TYPE_ACTION, '*NotAwesome*'),
        false,
    ];
    yield [
        $route,
        new RouteFilter(RouteFilter::FILTER_TYPE_MIDDLEWARE, '*api'),
        true,
    ];
    yield [
        $route,
        new RouteFilter(RouteFilter::FILTER_TYPE_MIDDLEWARE, 'web'),
        false,
    ];
});