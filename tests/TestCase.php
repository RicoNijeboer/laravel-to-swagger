<?php

namespace RicoNijeboer\Swagger\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use RicoNijeboer\Swagger\Providers\ValidationServiceProvider;
use RicoNijeboer\Swagger\SwaggerServiceProvider;
use RicoNijeboer\Swagger\Tests\app\Http\Controllers\TestController;
use RicoNijeboer\Swagger\Tests\Concerns\HelperMethods;
use Spatie\LaravelRay\RayServiceProvider;

class TestCase extends Orchestra
{
    use HelperMethods;

    public function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Spatie\\Swagger\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    protected function tearDown(): void
    {
        TestController::reset();
        parent::tearDown();
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        include_once __DIR__ . '/../database/migrations/create_swagger_batches_table.php.stub';
        (new \CreateSwaggerBatchesTable())->up();
        include_once __DIR__ . '/../database/migrations/create_swagger_entries_table.php.stub';
        (new \CreateSwaggerEntriesTable())->up();
    }

    protected function getPackageProviders($app)
    {
        return [
            ValidationServiceProvider::class,
            RayServiceProvider::class,
            SwaggerServiceProvider::class,
        ];
    }
}
