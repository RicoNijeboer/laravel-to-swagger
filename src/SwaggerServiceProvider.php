<?php

namespace RicoNijeboer\Swagger;

use Illuminate\Routing\Router;
use RicoNijeboer\Swagger\Http\Middleware\SwaggerReader;
use RicoNijeboer\Swagger\Http\Middleware\SwaggerTag;
use RicoNijeboer\Swagger\Providers\ValidationServiceProvider;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SwaggerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('swagger')
            ->hasConfigFile('swagger')
            ->hasMigration('create_swagger_batches_table')
            ->hasMigration('create_swagger_entries_table')
            ->hasMigration('create_swagger_tags_table')
            ->hasMigration('create_swagger_batch_tag_table');

        $this->ensureValidationServiceProviderLoaded();
        $this->registerMiddleware();
        $this->registerViews();
    }

    /**
     * @return void
     */
    protected function registerMiddleware(): void
    {
        $this->aliasMiddleware('tag', SwaggerTag::class);
        $this->aliasMiddleware('reader', SwaggerReader::class);

        // Making the reader a singleton ensures that the executed rules & applied tags still get stored.
        $this->app->singleton(SwaggerReader::class);
        $this->app->singleton(SwaggerTag::class);
    }

    /**
     * @param string $alias
     * @param string $class
     */
    protected function aliasMiddleware(string $alias, string $class)
    {
        /** @var Router $router */
        $router = $this->app['router'];
        $existingAliases = array_keys($router->getMiddleware());

        $swaggerAlias = 'swagger_' . $alias;
        $openapiAlias = 'openapi_' . $alias;

        if (!in_array($swaggerAlias, $existingAliases)) {
            $router->aliasMiddleware($swaggerAlias, $class);
        }
        if (!in_array($openapiAlias, $existingAliases)) {
            $router->aliasMiddleware($openapiAlias, $class);
        }
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom($this->package->basePath('/../resources/views'), $this->package->shortName());
    }

    protected function ensureValidationServiceProviderLoaded(): void
    {
        if (!$this->app->getProviders(ValidationServiceProvider::class)) {
            $this->app->register(ValidationServiceProvider::class);
        }
    }
}
