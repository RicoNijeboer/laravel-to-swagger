# Laravel to Swagger

![Packagist Version](https://img.shields.io/packagist/v/riconijeboer/laravel-to-swagger)
![Packagist Downloads](https://img.shields.io/packagist/dm/riconijeboer/laravel-to-swagger)

This package aims to bring the easiest path to creating a Swagger / OpenApi 3 config for your Laravel API's.

## Installation

1. `composer require riconijeboer/laravel-to-swagger`
1. `php artisan vendor:publish --provider="RicoNijeboer\Swagger\SwaggerServiceProvider"`
    - This will publish the package's config-file and migrations

### Requirements

- **PHP**: 7.4.x or 8.0.x
- **Laravel**: v6 / v7 / v8

### Updating

When changes are made that impact existing users, I will make sure that they are documented in the [Changelog](#changelog).

These will contain changes like database columns added / removed / renamed. Or functional changes that need action within your code.

## Usage

### Registering the Redoc Documentation route.

Reading a Swagger config is an acquired taste. To display the config in a more user-friendly way I've used [Redoc](https://github.com/Redocly/redoc). To register the documentation route you can simply
add the code below to your `routes/web.php` file or within a ServiceProvider.

```php
use RicoNijeboer\Swagger\Swagger;

Swagger::routes();
```

#### Customizing the /docs Url

```php
use RicoNijeboer\Swagger\Http\Routing\RouteRegistrar;
use RicoNijeboer\Swagger\Swagger;

Swagger::routes(fn (RouteRegistrar $routes) => $routes->forDocumentation('/different-url/docs'));
```

#### Customizing the routing group

```php
use RicoNijeboer\Swagger\Swagger;

Swagger::routes(null, [
    'prefix' => 'swagger', // This will do Route::group(['prefix' => 'swagger']) under the hood.
]);
```

#### Using Swagger UI instead of Redoc

You can disable Redoc and use Swagger UI instead by passing `false` as the second parameter to the `forDocumentation` method.

```php
use RicoNijeboer\Swagger\Http\Routing\RouteRegistrar;
use RicoNijeboer\Swagger\Swagger;

Swagger::routes(fn (RouteRegistrar $routes) => $routes->forDocumentation('/docs', false));
```

### Registering routes for your Swagger config

To add routes to your Swagger config you want to add the `\RicoNijeboer\Swagger\Http\Middleware\SwaggerReader` middleware. Which is aliased to both `swagger_reader` and `openapi_reader`.

```php
Route::middleware('swagger_reader')->get('products', [ProductController::class,'index']);
```

#### Tagging / Grouping routes

To group multiple routes in Swagger you add a tag to the path. Using the package you may register a tag through adding a middleware to your route and supplying it with the desired tags as shown below.

> Keep in mind that the `swagger_tag` (`\RicoNijeboer\Swagger\Http\Middleware\SwaggerTag`) middleware is only going to tag your request once the batch has been stored.
> If a batch has not been created it will continue and do nothing, no questions asked.

```php
// Using the SwaggerReader middleware
Route::middleware('swagger_reader:tag-one,tag-two')->get('products', [ProductController::class,'index']);

// Using the SwaggerTag middleware
Route::middleware('swagger_tag:tag-one,tag-two')->get('products', [ProductController::class,'index']);
```

## Configuration

### Database connection

You may want your database entries from the Laravel to Swagger package in a separate database or to have an extra prefix. To do this, add an extra connection to your Laravel project and just simply
set the `swagger.database.connection` config value.

For example, if I want all parts of the Swagger database to be in a connection I've named `swagger`, I'd set the config like below.

```php
return [
    //...
    'database' => [
        'connection' => 'swagger',
    ],
    //...
];
```

### Evaluation delay, configure how often your endpoints are re-evaluated

By default, the package only updates the configuration of your route every 12 hours, within a couple restraints. This does keep into account the response codes you are sending back to your users.

If you want to change this to for example 24 hours, you'd set the `swagger.evaluation-delay` config value to the time in seconds.

```php
return [
    //...
    'evaluation-delay' => 24 * 60 * 60, // Or 86400
    //...
];
```

### Swagger Meta Information and Servers

Swagger allows you to provide the users with a bit of info about your API; a title, a description and a version, and also an unlimited amount of servers to display in your UI.

In the config there is a `swagger.info` array where you can add your API's title, description and version, as shown below.  
There is also a `swagger.servers` array where you can add all the servers you'd like.

```php
return [
    //...
    'info'             => [
        'title'       => 'Laravel to Swagger',
        'description' => null,
        'version'     => '2.1.2',
    ],
    //...
    'servers'          => [
        [
            'url'         => 'http://api.example.com/v1',
            'description' => null,
        ],
        [
            'url'         => 'http://demo-api.example.com/v1',
            'description' => 'The demo environment description',
        ],
    ],
    //...
];
```

> _Currently the package does not support [Server templating](https://swagger.io/docs/specification/api-host-and-base-path), although I do plan on adding it [in the future](https://github.com/RicoNijeboer/laravel-to-swagger/issues/6)_

### Redoc Version

Let's say you run into a bug within the Redoc that the team behind Redoc has fixed in a newer version, and I have not updated the package yet to have the fixed version used.
I have added a config value (`swagger.redoc.version`), so you don't have to mess around with things like published views or anything;
You can simply change the version based on their [releases](https://github.com/Redocly/redoc/releases), at the time of writing this documentation the latest version is `v2.0.0-rc.53`.

```php
return [
    //...
    'redoc' => [
        'version' => 'v2.0.0-rc.53',
    ],
    //...
];
```

## Testing

```
composer test
```

## Credits

- [Rico Nijeboer](https://github.com/RicoNijeboer/)
- [All Contributors](https://github.com/RicoNijeboer/laravel-to-swagger/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Changelog

All notable changes can be found in the [CHANGELOG.md](docs/CHANGELOG.md).
