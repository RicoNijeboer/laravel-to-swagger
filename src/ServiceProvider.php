<?php

namespace Rico\Swagger;

use Rico\Swagger\Commands\ExportSwaggerCommand;
use Rico\Swagger\Commands\FilterCheckCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Class ServiceProvider
 *
 * @package Rico\Swagger
 */
class ServiceProvider extends PackageServiceProvider
{
    /**
     * @param Package $package
     */
    public function configurePackage(Package $package): void
    {
        $package->name('swagger')
            ->hasConfigFile('swagger')
            ->hasCommand(ExportSwaggerCommand::class)
            ->hasCommand(FilterCheckCommand::class);
    }
}