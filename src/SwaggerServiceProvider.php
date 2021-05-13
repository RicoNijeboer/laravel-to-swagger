<?php

namespace RicoNijeboer\Swagger;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SwaggerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-to-swagger')
            ->hasViews()
            ->hasConfigFile('swagger')
            ->hasMigration('create_swagger_batches_table')
            ->hasMigration('create_swagger_endpoints_table');
    }
}
