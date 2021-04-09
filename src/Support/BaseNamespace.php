<?php

namespace Rico\Swagger\Support;

use Facade\Ignition\Support\ComposerClassMap;
use Illuminate\Support\Arr;
use ReflectionProperty;

/**
 * Class BaseNamespace
 *
 * @package Rico\Swagger\Support
 */
class BaseNamespace
{
    public string $path;
    public string $namespace;

    public function __construct()
    {
        /** @var ComposerClassMap $classMap */
        $classMap = app(ComposerClassMap::class);

        $ref = new ReflectionProperty($classMap, 'composer');
        $ref->setAccessible(true);

        $prefixes = collect($ref->getValue($classMap)->getPrefixesPsr4())
            ->filter(function (array $paths, string $namespace) use ($classMap) {
                $realPaths = array_map('realpath', $paths);

                return Arr::first(
                        $realPaths,
                        function ($path) {
                            return $path === app()->basePath('app');
                        }
                    ) !== null;
            });

        $paths = $prefixes->first();

        $this->namespace = $prefixes->keys()->first();
        $this->path = realpath(array_pop($paths));
    }
}