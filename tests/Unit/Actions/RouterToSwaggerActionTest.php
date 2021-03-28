<?php

use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Rico\Swagger\Actions\RouterToSwaggerAction;
use Rico\Swagger\Exceptions\UnsupportedSwaggerExportTypeException;
use Rico\Swagger\Support\RouteFilter;
use Rico\Swagger\Swagger\Builder;

it('reads all routes when no `include`- or `exclude`-filters are provided', function () {
    $action = new RouterToSwaggerAction();
    $router = mock(Router::class, function (MockInterface $mock) {
        $routesProperty = property($mock, 'routes', true);

        $routeCollection = new RouteCollection();
        $routesProperty->setValue($mock, $routeCollection);

        $mock->shouldReceive('getRoutes')
            ->andReturnUsing(fn () => $routesProperty->getValue($mock));
    });

    property($action, 'router', true)->setValue($action, $router);

    expect($action->routes([]))
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(0);

    $routeCollection = new RouteCollection();
    $routeCollection->add(new Route(['GET'], '/orders', fn () => null));
    property($router, 'routes', true)->setValue($router, $routeCollection);

    expect($action->routes([]))
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(1);
});

it('does not read the routes that match the `exclude`-filters', function () {
    $action = new RouterToSwaggerAction();
    $router = mock(Router::class, function (MockInterface $mock) {
        $routesProperty = property($mock, 'routes', true);

        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], '/orders', fn () => null));
        $routeCollection->add(new Route(['GET'], '/products', fn () => null));
        $routesProperty->setValue($mock, $routeCollection);

        $mock->shouldReceive('getRoutes')
            ->andReturnUsing(fn () => $routesProperty->getValue($mock));
    });
    property($action, 'router', true)->setValue($action, $router);

    $result = $action->routes([new RouteFilter('uri', '*order*')]);

    expect($result)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->and($result->first())
        ->toBeInstanceOf(Route::class);

    $result->each(function (Route $route) {
        expect($route)
            ->toHaveProperty('uri', 'products')
            ->not()->toHaveProperty('uri', 'orders');
    });
});

it('only reads the routes that should match the `include`-filters', function () {
    $action = new RouterToSwaggerAction();
    $router = mock(Router::class, function (MockInterface $mock) {
        $routesProperty = property($mock, 'routes', true);

        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], '/orders', fn () => null));
        $routeCollection->add(new Route(['GET'], '/products', fn () => null));
        $routesProperty->setValue($mock, $routeCollection);

        $mock->shouldReceive('getRoutes')
            ->andReturnUsing(fn () => $routesProperty->getValue($mock));
    });
    property($action, 'router', true)->setValue($action, $router);

    $result = $action->routes([], [new RouteFilter('uri', '*order*')]);

    expect($result)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->and($result->first())
        ->toBeInstanceOf(Route::class);

    $result->each(function (Route $route) {
        expect($route)
            ->toHaveProperty('uri', 'orders')
            ->not()->toHaveProperty('uri', 'products');
    });
});

it('will not read a route that matches an `include`-filter and an `exclude`-filter', function () {
    $action = new RouterToSwaggerAction();
    $router = mock(Router::class, function (MockInterface $mock) {
        $routesProperty = property($mock, 'routes', true);

        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], '/orders', fn () => null));
        $routesProperty->setValue($mock, $routeCollection);

        $mock->shouldReceive('getRoutes')
            ->andReturnUsing(fn () => $routesProperty->getValue($mock));
    });
    property($action, 'router', true)->setValue($action, $router);

    $filter = new RouteFilter('uri', '*order*');
    $result = $action->routes([$filter], [$filter]);

    expect($result)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(0);
});

it('can export the Swagger config in `yaml` format', function () {
    $action = new RouterToSwaggerAction();
    $export = method($action, 'export', true);

    $swagger = mock(Builder::class, function (MockInterface $mock) {
        $mock->shouldReceive('toYaml')
            ->once()
            ->andReturn('TYPE: YAML');
    });

    property($action, 'swagger', true)->setValue($action, $swagger);

    expect($export->invoke($action, RouterToSwaggerAction::TYPE_YAML))
        ->toBe('TYPE: YAML');
});

it('can export the Swagger config in `json` format', function () {
    $action = new RouterToSwaggerAction();
    $export = method($action, 'export', true);

    $swagger = mock(Builder::class, function (MockInterface $mock) {
        $mock->shouldReceive('toJson')
            ->once()
            ->andReturn('TYPE: JSON');
    });

    property($action, 'swagger', true)->setValue($action, $swagger);

    expect($export->invoke($action, RouterToSwaggerAction::TYPE_JSON))
        ->toBe('TYPE: JSON');
});

it('can export the Swagger config in `array` format', function () {
    $action = new RouterToSwaggerAction();
    $export = method($action, 'export', true);

    $swagger = mock(Builder::class, function (MockInterface $mock) {
        $mock->shouldReceive('toArray')
            ->once()
            ->andReturn([
                'type' => 'ARRAY',
            ]);
    });

    property($action, 'swagger', true)->setValue($action, $swagger);

    expect($export->invoke($action, RouterToSwaggerAction::TYPE_ARRAY))
        ->toBeArray()
        ->toHaveKey('type', 'ARRAY');
});

it('will throw an exception when export() is called with an invalid type', function () {
    $action = new RouterToSwaggerAction();
    $export = method($action, 'export', true);

    $export->invoke($action, PHP_INT_MIN);
})->throws(UnsupportedSwaggerExportTypeException::class);

it('will throw an exception when convert() is called with an invalid type', function () {
    $action = new RouterToSwaggerAction();

    $action->convert(mock(Router::class), null, null, null, [], [], [], [], PHP_INT_MIN);
})->throws(UnsupportedSwaggerExportTypeException::class);