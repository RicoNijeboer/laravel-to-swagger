<?php

namespace Rico\Swagger\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Laravel\Passport\Scope;
use ReflectionException;
use Rico\Reader\Exceptions\EndpointDoesntExistException;
use Rico\Swagger\Actions\RouterToSwaggerAction;
use Rico\Swagger\Exceptions\UnsupportedSwaggerExportTypeException;
use Rico\Swagger\Routing\RouteMiddlewareHelper;
use Rico\Swagger\Support\RouteFilter;
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
                            { --T|title= : Add a title to your Swagger config }
                            { --D|description= : Add a description to your Swagger config }
                            { --set-version= : Sets the version off the Swagger config }
                            { --O|out=swagger.yml : The output path, can be both relative and the full path }
                            { --s|server=* : Servers to add }
                            { --t|tag=* : Tag a part of your endpoints using the filter syntax }
                            { --i|include=* : Only include a part of your endpoints using the filter syntax (when no filters are provided it will use all routes) }
                            { --e|exclude=* : Exclude a part of your endpoints using the filter syntax }';
    private string $outputPath;
    private bool $yaml = true;
    private RouteMiddlewareHelper $routeMiddlewareHelper;

    /**
     * ExportSwaggerCommand constructor.
     *
     * @param RouteMiddlewareHelper $resolver
     */
    public function __construct(RouteMiddlewareHelper $resolver)
    {
        parent::__construct();
        $this->routeMiddlewareHelper = $resolver;
    }

    /**
     * @param Router                $router
     * @param RouterToSwaggerAction $action
     *
     * @throws BindingResolutionException
     * @throws EndpointDoesntExistException
     * @throws UnsupportedSwaggerExportTypeException
     * @throws ReflectionException
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
            $this->getExclude(),
            $this->getInclude(),
            $this->yaml ? RouterToSwaggerAction::TYPE_YAML : RouterToSwaggerAction::TYPE_JSON,
            $this->oauthConfig($router)
        );

        File::put($this->outputPath, $fileContent);
    }

    /**
     * @return bool
     */
    protected function hasLaravelPassport(): bool
    {
        return count(app()->getProviders(\Laravel\Passport\PassportServiceProvider::class)) !== 0;
    }

    /**
     * @param Router $router
     *
     * @return array
     */
    protected function oauthConfig(Router $router): array
    {
        $config = ['enabled' => $this->hasLaravelPassport()];

        if ($config['enabled']) {
            $clients = Passport::client()
                ->newQuery()
                ->get();

            $config['scopes'] = Passport::scopes()
                ->mapWithKeys(fn (Scope $scope) => [
                    $scope->id => $scope->description,
                ])
                ->all();

            $hasTokenUrl = $router->has('passport.token');
            $hasAuthorizationUrl = $router->has('passport.authorizations.authorize');

            $config['flows'] = [
                'clientCredentials' => [
                    'enabled'  => $clients->filter->password_client->isNotEmpty() && $hasTokenUrl,
                    'tokenUrl' => $hasTokenUrl ? route('passport.token') : null,
                ],
                'password'          => [
                    'enabled'  => $clients->filter->confidential()->isNotEmpty() && $hasTokenUrl,
                    'tokenUrl' => $hasTokenUrl ? route('passport.token') : null,
                ],
                'authorizationCode' => [
                    'enabled'          => $clients
                            ->filter(fn (Client $client) => !$client->firstParty())
                            ->isNotEmpty() && $hasTokenUrl && $hasAuthorizationUrl,
                    'tokenUrl'         => $hasTokenUrl ? route('passport.token') : null,
                    'authorizationUrl' => $hasAuthorizationUrl ? route('passport.authorizations.authorize') : null,
                ],
                'implicit'          => [
                    'enabled'          => $clients
                            ->filter(
                                fn (Client $client) => !(
                                    is_array($client->grant_types)
                                    && !in_array('implicit', $client->grant_types)
                                )
                            )
                            ->isNotEmpty() && $hasTokenUrl && $hasAuthorizationUrl,
                    'tokenUrl'         => $hasTokenUrl ? route('passport.token') : null,
                    'authorizationUrl' => $hasAuthorizationUrl ? route('passport.authorizations.authorize') : null,
                ],
            ];
        }

        return $config;
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
        return array_map(function (string $input): Tag {
            [$tagName, $filterInput] = explode(';', $input, 2);

            return array_reduce(
                RouteFilter::extract($filterInput),
                fn (Tag $tag, RouteFilter $filter) => $tag->addFilter($filter),
                Tag::new($tagName)
            );
        }, $this->option('tag'));
    }

    /**
     * @return RouteFilter[]
     */
    protected function getExclude(): array
    {
        $exclude = $this->option('exclude');

        // Because Laravel telescope breaks everything we exclude it by default (I'm sorry <3).
        $telescopeFilter = new RouteFilter(RouteFilter::FILTER_TYPE_URI, '*telescope*');
        if (!$telescopeFilter->arrayMatches($exclude)) {
            $exclude[] = "{$telescopeFilter->getType()}:'{$telescopeFilter->getFilter()}'";
        }

        return array_reduce(
            $exclude,
            fn (array $filters, string $filter) => array_merge($filters, RouteFilter::extract($filter)),
            []
        );
    }

    /**
     * @return RouteFilter[]
     */
    protected function getInclude(): array
    {
        return array_reduce(
            $this->option('include'),
            fn (array $filters, string $filter) => array_merge($filters, RouteFilter::extract($filter)),
            []
        );
    }
}