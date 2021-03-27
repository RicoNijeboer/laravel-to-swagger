<?php

use Rico\Reader\Endpoints\EndpointData;
use Rico\Swagger\Swagger\Endpoint;

it('can read properties from Laravel request rules', function () {
    $propertiesProp = property(Endpoint::class, 'properties', true);

    $indexRoute = mockRoute('orders', ['api'], ['controller' => 'OrderController', 'uses' => 'OrderController@index']);

    $index = new EndpointData();
    $index->setRoute($indexRoute);
    $index->addRules([
        'name'                     => ['required', 'string'],
        'email'                    => ['required', 'email'],
        'phone'                    => ['nullable', 'string'],
        'notifications.apiChanges' => ['boolean'],
    ]);

    $endpoint = new Endpoint($index);

    $properties = $propertiesProp->getValue($endpoint);

    expect($properties)
        ->toHaveKeys(['name', 'email', 'phone', 'notifications'])
        ->not()->toHaveKey('apiChanges')
        ->not()->toHaveKey('notifications.apiChanges');

    expect($properties['name'])
        ->toBeArray()
        ->toHaveKey('type', 'string')
        ->not()->toHaveKey('nullable')
        ->not()->toHaveKey('format')
        ->not()->toHaveKey('example')
        ->not()->toHaveKey('properties');

    expect($properties['email'])
        ->toBeArray()
        ->toHaveKey('type', 'string')
        ->not()->toHaveKey('nullable')
        ->toHaveKey('format', 'email')
        ->not()->toHaveKey('example')
        ->not()->toHaveKey('properties');

    expect($properties['phone'])
        ->toBeArray()
        ->toHaveKey('type', 'string')
        ->toHaveKey('nullable')
        ->not()->toHaveKey('format')
        ->toHaveKey('example', null)
        ->not()->toHaveKey('properties');

    expect($properties['notifications'])
        ->toBeArray()
        ->toHaveKey('type', 'object')
        ->not()->toHaveKey('nullable')
        ->not()->toHaveKey('format', 'email')
        ->not()->toHaveKey('example')
        ->toHaveKey('properties')
        ->and($properties['notifications']['properties'])
        ->toBeArray()->toHaveCount(1)
        ->toHaveKey('apiChanges')
        ->and($properties['notifications']['properties']['apiChanges'])
        ->toHaveKey('type', 'boolean')
        ->toHaveKey('nullable')
        ->not()->toHaveKey('format')
        ->toHaveKey('example', null)
        ->not()->toHaveKey('properties');
});