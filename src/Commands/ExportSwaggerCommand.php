<?php

namespace Rico\Swagger\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Rico\Reader\Exceptions\EndpointDoesntExistException;
use Rico\Swagger\Actions\RouterToSwaggerAction;
use Rico\Swagger\Exceptions\UnsupportedSwaggerExportTypeException;
use Rico\Swagger\Swagger\Formatter\Formatter;
use Rico\Swagger\Swagger\Server;
use Rico\Swagger\Swagger\Tag;

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
                            {--s|server=* : Servers to add}
                            {--t|tag=* : Tag a part of your endpoints using a specified syntax}';

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
            $this->getServers(),
            $this->getTags(),
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
     * @return Server[]
     */
    protected function getServers(): array
    {
        return array_map(
            function (string $server) {
                $serverInfo = explode(' ', $server . ' ', 2);

                return new Server($serverInfo[0], $serverInfo[1] ?? null);
            },
            $this->option('server')
        );
    }

    /**
     * @return array
     */
    protected function getTags(): array
    {
        return array_map(function (string $tag): Tag {
            $tag = str_replace(['%'], '*', $tag);
            $filters = explode(';', $tag);

            $tag = array_shift($filters);

            return array_reduce(
                $filters,
                function (Tag $t, string $filter) {
                    [$endpointFilters, $middlewareFilters, $controllerFilters] = $this->readTagFilter($filter);

                    array_walk($endpointFilters, fn(string $f) => $t->addEndpointFilter(trim($f)));
                    array_walk($middlewareFilters, fn(string $f) => $t->addMiddlewareFilter(trim($f)));
                    array_walk($controllerFilters, fn(string $f) => $t->addControllerFilter(trim($f)));

                    return $t;
                },
                Tag::new($tag),
            );
        }, $this->option('tag'));
    }

    /**
     * Read the tag filter into endpoint and middleware filters.
     *
     * @param string $filters
     *
     * @return array
     */
    protected function readTagFilter(string $filters): array
    {
        preg_match_all(
            '/(\se:([\w\/*\-%]*))|(\sm:([\w*%]*))|(\sc:([\w*%]*))/m',
            $filters,
            $matches
        );

        $endpointFilters = array_values(array_filter($matches[2], fn(string $filter) => ! empty($filter)));
        $middlewareFilters = array_values(array_filter($matches[4], fn(string $filter) => ! empty($filter)));
        $controllerFilters = array_values(array_filter($matches[6], fn(string $filter) => ! empty($filter)));

        return [
            $endpointFilters,
            $middlewareFilters,
            $controllerFilters,
        ];
    }
}