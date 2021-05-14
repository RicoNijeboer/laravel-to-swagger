<?php

namespace RicoNijeboer\Swagger;

use Illuminate\Routing\Router;
use RicoNijeboer\Swagger\Http\Middleware\SwaggerReader;
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
            ->hasMigration('create_swagger_entries_table')
            ->hasMigration('create_swagger_tags_table')
            ->hasMigration('create_swagger_batch_tag_table');

        $this->registerMiddleware();
    }

    /**
     * @return void
     */
    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];

        $existingAliases = array_keys($router->getMiddleware());

        if (!in_array('swagger', $existingAliases)) {
            $router->aliasMiddleware('swagger', SwaggerReader::class);
        }
        if (!in_array('openapi', $existingAliases)) {
            $router->aliasMiddleware('openapi', SwaggerReader::class);
        }
    }
}
