<?php

namespace RicoNijeboer\Swagger;

use Illuminate\Routing\Router;
use RicoNijeboer\Swagger\Middleware\SwaggerReader;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SwaggerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('swagger')
            ->hasViews()
            ->hasConfigFile('swagger')
            ->hasMigration('create_swagger_batches_table')
            ->hasMigration('create_swagger_entries_table');

        $this->registerMiddleware();
    }

    /**
     * @return void
     */
    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];

        $router->aliasMiddleware('swagger', SwaggerReader::class);
        $router->aliasMiddleware('openapi', SwaggerReader::class);
    }
}
