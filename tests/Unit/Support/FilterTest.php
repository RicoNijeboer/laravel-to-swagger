<?php

use Rico\Swagger\Support\Filter;
use Rico\Swagger\Tests\TestCase;

it('can extract the expected filters from the provided filter', function (string $input, string $expectedType, string $expectedFilter) {
    [$filter] = Filter::extract($input);

    expect($filter->getType())
        ->toBe($expectedType)
        ->and($filter->getFilter())
        ->toBe($expectedFilter);
})->with([
    ['type:*test*', 'type', '*test*'],
    ['type:"*:*test*"', 'type', '*:*test*'],
    ['other:"*:*test*"', 'other', '*:*test*'],
    ['other:\'*:*test*\'', 'other', '*:*test*'],
]);

it('can extract multiple filters when the filter should contain multiple filters', function (string $input, array $expectedTypes, array $expectedFilters) {
    $filters = Filter::extract($input);

    expect($filters)->toHaveCount(count($expectedTypes));

    foreach ($expectedTypes as $index => $expectedType) {
        expect($filters[$index]->getType())
            ->toBe($expectedType);
    }

    foreach ($expectedFilters as $index => $expectedFilter) {
        expect($filters[$index]->getFilter())
            ->toBe($expectedFilter);
    }
})->with([
    ['one:test two:not-test', ['one', 'two'], ['test', 'not-test']],
    ['one:\'test:test\' two:"not-test"', ['one', 'two'], ['test:test', 'not-test']],
]);

it(
    'can see if it a value matches the filter',
    function (array $filter, string $value, bool $expected) {
        $filter = new Filter(...$filter);

        expect($filter->matches($value))->toBe($expected);
    }
)->with([
    [['test', '*order*'], 'some order string', true],
    [['test', '*order*'], 'some string', false],
]);

it(
    'can see if an array of strings matches the filter',
    function (array $filter, array $value, bool $expected) {
        $filter = new Filter(...$filter);

        expect($filter->arrayMatches($value))->toBe($expected);
    }
)->with([
    [['test', '*order*'], ['some order string', 'different string'], true],
    [['test', '*order*'], ['some string', 'some order string'], true],
    [['test', '*order*'], ['some string', 'some other string'], false],
]);

test(
    'you can check the type yourself using the `isType($type)` helper',
    function (array $filter, string $checkedType, bool $expected) {
        $filter = new Filter(...$filter);

        expect($filter->isType($checkedType))->toBe($expected);
    }
)->with([
    [['typeOne', '*'], 'typeOne', true],
    [['typeOne', '*'], 'type*', true],
    [['typeOne', '*'], '*One', true],
    [['typeOne', '*'], 'typeTwo', false],
    [['typeOne', '*'], '*Two', false],
]);