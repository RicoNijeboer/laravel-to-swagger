<?php

namespace Rico\Swagger;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Rico\Swagger\Actions\RouterToOAuthConfig;
use Rico\Swagger\Routing\RouteRegistrar;
use Rico\Swagger\Support\RouteFilter;
use Rico\Swagger\Swagger\Server;
use Rico\Swagger\Swagger\Tag;

/**
 * Class Swagger
 *
 * @package Rico\Swagger
 */
class Swagger
{
    const OAUTH_LARAVEL_PASSPORT = 10;
    protected static int $oauthImplementation = self::OAUTH_LARAVEL_PASSPORT;
    /** @var Server[] */
    protected static array $servers = [];
    /** @var Tag[] */
    protected static array $tags = [];
    /** @var RouteFilter[] */
    protected static array $include = [];
    /** @var RouteFilter[] */
    protected static array $exclude = [];
    protected static $configUri = '/_swagger/config';

    /**
     * @param null  $callback
     * @param array $options
     */
    public static function routes($callback = null, array $options = []): void
    {
        $callback = $callback ?? fn (RouteRegistrar $registrar) => null;

        $defaultOptions = [
            'prefix'    => '/_swagger',
            'as'        => 'swagger.',
            'namespace' => '\Rico\Swagger\Http\Controllers',
        ];

        $options = array_merge($defaultOptions, $options);

        Route::group($options, function (Router $router) use ($callback) {
            $callback(new RouteRegistrar($router));
        });
    }

    /**
     * @param Server[]|null      $servers
     * @param Tag[]|null         $tags
     * @param RouteFilter[]|null $include
     * @param RouteFilter[]|null $exclude
     * @param int                $oauthImplementation
     */
    public static function configure(
        array $servers = null,
        array $tags = null,
        array $include = null,
        array $exclude = null,
        int $oauthImplementation = self::OAUTH_LARAVEL_PASSPORT
    ): void {
        static::servers($servers);
        static::tags($tags);
        static::include($include);
        static::exclude($exclude);
        static::oauthImplementation($oauthImplementation);
    }

    /**
     * @param Server[]|null $servers
     *
     * @return mixed|void
     */
    public static function servers(array $servers = null)
    {
        if (is_null($servers)) {
            return static::$servers;
        }

        static::$servers = $servers;
    }

    /**
     * @param Tag[]|null $tags
     *
     * @return mixed|void
     */
    public static function tags(array $tags = null)
    {
        if (is_null($tags)) {
            return static::$tags;
        }

        static::$tags = $tags;
    }

    /**
     * @param RouteFilter[]|null $include
     *
     * @return mixed|void
     */
    public static function include(array $include = null)
    {
        if (is_null($include)) {
            return static::$include;
        }

        static::$include = $include;
    }

    /**
     * @param RouteFilter[]|null $exclude
     *
     * @return mixed|void
     */
    public static function exclude(array $exclude = null)
    {
        if (!is_null($exclude)) {
            static::$exclude = $exclude;
        }

        if (empty(static::$exclude)) {
            return [
                new RouteFilter(RouteFilter::FILTER_TYPE_URI, '*telescope*'),
                new RouteFilter(RouteFilter::FILTER_TYPE_URI, '*ignition*'),
                new RouteFilter(RouteFilter::FILTER_TYPE_URI, '*swagger*'),
            ];
        }

        return static::$exclude;
    }

    /**
     * @param Router $router
     *
     * @return array
     */
    public static function oauthConfig(Router $router): array
    {
        /** @var RouterToOAuthConfig $action */
        $action = app(RouterToOAuthConfig::class);

        switch (static::$oauthImplementation) {
            case static::OAUTH_LARAVEL_PASSPORT:
            default:
                return $action->laravelPassport($router);
        }
    }

    /**
     * @param int|null $oauthImplementation
     *
     * @return int|void
     */
    public static function oauthImplementation(int $oauthImplementation = null)
    {
        if (is_null($oauthImplementation)) {
            return static::$oauthImplementation;
        }

        static::$oauthImplementation = $oauthImplementation;
    }

    /**
     * @param string|null $uri
     *
     * @return string|void
     */
    public static function configUri(string $uri = null)
    {
        if (is_null($uri)) {
            return static::$configUri;
        }

        static::$configUri = Str::startsWith($uri, ['http://', 'https://']) ? $uri : Str::start($uri, '/');
    }
}