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
