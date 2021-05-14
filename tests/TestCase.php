<?php

namespace RicoNijeboer\Swagger\Tests;

use Cerbero\LazyJson\Providers\LazyJsonServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Laravel\Passport\PassportServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RicoNijeboer\Swagger\Providers\ValidationServiceProvider;
use RicoNijeboer\Swagger\Support\Concerns\HelperMethods;
use RicoNijeboer\Swagger\SwaggerServiceProvider;
use RicoNijeboer\Swagger\Tests\app\Http\Controllers\TestController;
use RicoNijeboer\Swagger\Tests\Concerns\CustomAssertions;
use Spatie\LaravelRay\RayServiceProvider;

class TestCase extends Orchestra
{
    use HelperMethods,
        CustomAssertions;

    public function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'RicoNijeboer\\Swagger\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('passport.storage.database.connection', 'testing');

        $packageMigrations = scandir(database_path('migrations'));

        foreach ($packageMigrations as $packageMigrationPath) {
            if (!Str::contains($packageMigrationPath, 'php')) {
                continue;
            }

            require_once(database_path('migrations') . DIRECTORY_SEPARATOR . $packageMigrationPath);
            $migrationClass = Str::studly(implode('_', array_slice(explode('_', basename($packageMigrationPath, '.php')), 4)));

            (new $migrationClass())->up();
        }

        include_once __DIR__ . '/../database/migrations/create_swagger_batches_table.php.stub';
        (new \CreateSwaggerBatchesTable())->up();
        include_once __DIR__ . '/../database/migrations/create_swagger_entries_table.php.stub';
        (new \CreateSwaggerEntriesTable())->up();
        include_once __DIR__ . '/../database/migrations/create_swagger_tags_table.php.stub';
        (new \CreateSwaggerTagsTable())->up();
        include_once __DIR__ . '/../database/migrations/create_swagger_batch_tag_table.php.stub';
        (new \CreateSwaggerBatchTagTable())->up();
    }

    protected function tearDown(): void
    {
        TestController::reset();
        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            RayServiceProvider::class,
            PassportServiceProvider::class,
            LazyJsonServiceProvider::class,
            SwaggerServiceProvider::class,
            ValidationServiceProvider::class,
        ];
    }
}
