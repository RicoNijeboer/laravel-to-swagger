<?php

namespace Rico\Swagger\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Rico\Reader\Endpoints\EndpointData;
use Rico\Reader\Endpoints\EndpointReader;
use Rico\Reader\Exceptions\EndpointDoesntExistException;
use Rico\Swagger\Swagger\Builder;
use Rico\Swagger\Swagger\Formatter\Formatter;

/**
 * Class ExportSwaggerCommand
 *
 * @package Rico\Swagger\Commands
 */
class ExportSwaggerCommand extends Command
{

    /** @var string */
    protected $signature = 'api:swagger
                            {--T|title=}
                            {--D|description=}
                            {--set-version=}
                            {--O|out=swagger.yml}
                            {--s|server=* : Servers to add}';

    private string $outputPath;
    private bool $yaml = true;

    /**
     * @param Router         $router
     * @param EndpointReader $endpointReader
     *
     * @throws EndpointDoesntExistException
     */
    public function handle(Router $router)
    {
        $this->setOutputPath();

        $swagger = Builder::new($this->option('title'), $this->option('description'), $this->option('set-version'));

        $this->getRoutes($router)
             ->each(function (Route $route) use ($swagger) {
                 $swagger->addPath(
                     $route->uri(),
                     EndpointReader::readRoute($route)->all()
                 );
             });

        $this->getServers()->each(fn(string $server) => $swagger->addServer($server));

        File::put($this->outputPath, $this->yaml ? $swagger->toYaml() : $swagger->toJson());
    }

    /**
     * Set the output path based on the --out option.
     */
    protected function setOutputPath()
    {
        /** @var string $out */
        $out = $this->option('out');

        if (Str::endsWith($out, '.json')) {
            $this->yaml = false;
        }

        $this->outputPath = Str::startsWith($out, DIRECTORY_SEPARATOR)
            ? $out : implode(DIRECTORY_SEPARATOR, [getcwd(), $out]);
    }

    /**
     * @param Router $router
     *
     * @return Collection
     */
    protected function getRoutes(Router $router): Collection
    {
        return collect($router->getRoutes()->getRoutes());
    }

    /**
     * @return Collection
     */
    protected function getServers(): Collection
    {
        return collect($this->option('server'));
    }
}