<?php

namespace Rico\Swagger\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Rico\Reader\Exceptions\EndpointDoesntExistException;
use Rico\Swagger\Actions\RouterToSwaggerAction;
use Rico\Swagger\Exceptions\UnsupportedSwaggerExportTypeException;
use Rico\Swagger\Swagger\Formatter\Formatter;
use Rico\Swagger\Swagger\Server;

/**
 * Class ExportSwaggerCommand
 *
 * @package Rico\Swagger\Commands
 */
class ExportSwaggerCommand extends Command
{

    /** @var string */
    protected $signature = 'api:swagger
                            {--T|title= : Add a title to your Swagger config}
                            {--D|description= : Add a description to your Swagger config}
                            {--set-version= : Sets the version off the Swagger config}
                            {--O|out=swagger.yml : The output path, can be both relative and the full path}
                            {--s|server=* : Servers to add}';

    private string $outputPath;

    private bool $yaml = true;

    /**
     * @param Router                $router
     * @param RouterToSwaggerAction $action
     *
     * @throws EndpointDoesntExistException
     * @throws BindingResolutionException
     * @throws UnsupportedSwaggerExportTypeException
     */
    public function handle(Router $router, RouterToSwaggerAction $action)
    {
        $this->setOutputPath();

        $fileContent = $action->convert(
            $router,
            $this->option('title'),
            $this->option('description'),
            $this->option('set-version'),
            array_map(fn(string $url) => new Server($url), $this->getServers()),
            $this->yaml ? RouterToSwaggerAction::TYPE_YAML : RouterToSwaggerAction::TYPE_JSON,
        );

        File::put($this->outputPath, $fileContent);
    }

    /**
     * Set the output path based on the --out option.
     */
    protected function setOutputPath()
    {
        /** @var string $out */
        $out = $this->option('out');

        if (Str::endsWith($out, '.json'))
        {
            $this->yaml = false;
        }

        $this->outputPath = Str::startsWith($out, DIRECTORY_SEPARATOR)
            ? $out : implode(DIRECTORY_SEPARATOR, [getcwd(), $out]);
    }

    /**
     * @return string[]
     */
    protected function getServers(): array
    {
        return $this->option('server');
    }
}