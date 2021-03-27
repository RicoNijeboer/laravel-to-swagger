<?php

use Illuminate\Support\Collection;
use Rico\Reader\Endpoints\EndpointData;
use Rico\Swagger\Swagger\Builder;
use Rico\Swagger\Swagger\Endpoint;
use Rico\Swagger\Swagger\Server;

it('has a default for all info', function (string $field, string $default) {
    $builder = new Builder();

    $info = property($builder, 'info', true)->getValue($builder);

    expect($info)
        ->toHaveKey($field, $default);
})->with([
    ['title', ' '],
    ['description', ' '],
    ['version', 'v0.0.1'],
]);

it('has a has the openapi version', function () {
    $builder = new Builder();

    $openapi = property($builder, 'openapi', true)->getValue($builder);

    expect($openapi)->toBe('3.0.0');
});

it('has an empty array of tags by default', function () {
    $builder = new Builder();

    expect(property($builder, 'tags', true)->getValue($builder))
        ->toBeArray()->toHaveCount(0);
});

it('has an empty array of paths by default', function () {
    $builder = new Builder();

    expect(property($builder, 'paths', true)->getValue($builder))
        ->toBeArray()->toHaveCount(0);
});

it('has the minimal swagger configuration by default', function () {
    $builder = new Builder();

    expect($builder->toArray())
        ->toHaveCount(2)
        ->toHaveKey('openapi', '3.0.0')
        ->toHaveKey('info')
        ->and($builder->toArray()['info'])
        ->toHaveKey('title', ' ')
        ->toHaveKey('description', ' ')
        ->toHaveKey('version', 'v0.0.1');
});

it('merges paths on the same URI per method', function () {
    $builder = new Builder();
    $property = property($builder, 'paths', true);

    $indexRoute = mockRoute('orders', ['api'], ['controller' => 'OrderController', 'uses' => 'OrderController@index']);
    $index = new EndpointData();
    $index->setRoute($indexRoute);

    $storeRoute = mockRoute('orders', ['api'], ['controller' => 'OrderController', 'uses' => 'OrderController@store']);
    $store = new EndpointData();
    $store->setRoute($storeRoute);

    $builder->addPath('orders', [
        'get'  => $index,
        'post' => $store,
    ]);

    $paths = $property->getValue($builder);

    expect($paths)
        ->toBeArray()
        ->toHaveCount(1)
        ->toHaveKey('/orders')
        ->and($paths['/orders'])
        ->toBeInstanceOf(Collection::class)
        ->toHaveKeys(['get', 'post'])
        ->and($paths['/orders']['get'])
        ->toBeInstanceOf(Endpoint::class)
        ->and($paths['/orders']['post'])
        ->toBeInstanceOf(Endpoint::class);
});

it('returns the path when the configuration is built', function () {
    $builder = new Builder();

    $indexRoute = mockRoute('orders', ['api'], ['controller' => 'OrderController', 'uses' => 'OrderController@index']);
    $index = new EndpointData();
    $index->setRoute($indexRoute);

    $storeRoute = mockRoute('orders', ['api'], ['controller' => 'OrderController', 'uses' => 'OrderController@store']);
    $store = new EndpointData();
    $store->setRoute($storeRoute);

    $builder->addPath('orders', [
        'get'  => $index,
        'post' => $store,
    ]);

    $config = $builder->toArray();

    expect($config)
        ->toHaveKey('paths')
        ->and($config['paths'])
        ->toHaveKey('/orders')
        ->and($config['paths']['/orders'])
        ->toHaveKeys(['get', 'post']);
});

it('can add server(s) to the exported Swagger config', function () {
    $builder = new Builder();
    $property = property($builder, 'servers', true);

    $builder->addServer(new Server('api.example.com'));
    $builder->addServer(new Server('test-api.example.com', 'Test environment'));

    $servers = $property->getValue($builder);
    expect($servers)
        ->toBeArray()->toHaveCount(2)
        ->and($servers[0])
        ->toBeInstanceOf(Server::class)
        ->and($servers[0]->toArray())
        ->toHaveCount(1)
        ->toHaveKey('url', 'api.example.com')
        ->and($servers[1])
        ->toBeInstanceOf(Server::class)
        ->and($servers[1]->toArray())
        ->toHaveCount(2)
        ->toHaveKey('url', 'test-api.example.com')
        ->toHaveKey('description', 'Test environment')
        ->and($builder->toArray())
        ->toHaveKey('servers')
        ->and($builder->toArray()['servers'])
        ->toHaveCount(2);
});