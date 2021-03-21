<?php

namespace Rico\Swagger;

use Rico\Swagger\Commands\ExportSwaggerCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Class ServiceProvider
 *
 * @package Rico\Swagger
 */
class ServiceProvider extends PackageServiceProvider
{

    public function configurePackage(Package $package): void
    {
        $package->name('swagger')
                ->hasCommand(ExportSwaggerCommand::class);
    }
}