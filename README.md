# Laravel to Swagger

Export your routes to a basic Swagger config the properties of your request (and their data types wherever I can).

# Disclaimer

When exporting, a part of your code **will** be executed. I've made sure *no* should go to your database as I've overwritten the Laravel database connection resolver to always return a connection
which throws an Exception which is used to determine when to stop listening for validation errors.

Because of this I **will** not and **do** not take responsibility for any issues resulting from an export created using this package. Unless of course it directly impacts the functionality of this
package.

# Requirements

- This package requires PHP 7.4 or higher.
- Laravel 6 / 7 / 8

# Installation

```shell
composer require riconijeboer/laravel-to-swagger
```

# Routes

You can also add a routes to your application using which you can expose the Swagger config, which people can use to import from a URL using Insomnia, Postman or any other tool that supports Swagger /
OpenAPI 3.0.

You do this by using the `\Rico\Swagger\Swagger::routes` helper and passing a callback. This callback receives a RouteRegistrar with helpers to add the routes.

## Config Route

To register the config URL in your router you can use the `forSwaggerConfig` method on the route registrar as shown below. This creates the following route;

- `/_swagger/config`
    - Instead needing to create the file and exposing it this route converts your application in real-time.
        - To convert your application it uses a custom IOT container, that way I don't overwrite anything inside the container that your Laravel application uses.

```php
use Rico\Swagger\Swagger;
use Rico\Swagger\Routing\RouteRegistrar;

Swagger::routes(function (RouteRegistrar $registrar) {
    $registrar->forSwaggerConfig();
});
```

## Documentation route

To register the documentation URL in your router you can use the `forDocumentation` method on the route registrar as shown below. This creates two routes;

- `/_swagger/documentation`
    - The page where the Swagger documentation is displayed, powered by Redoc
- `/_swagger/documentation/config`
    - To display the documentation a route needs to be exposed exposing the documentation. This route is given the `signed` middleware that Laravel provides to ensure that it is only accessible when
      you actually want to retrieve it. It does exactly the same as the [config route](#config-route) but is simply added to ensure the config route exists.

```php
use Rico\Swagger\Swagger;
use Rico\Swagger\Routing\RouteRegistrar;

Swagger::routes(function (RouteRegistrar $registrar) {
    $registrar->forDocumentation();
});
```

# Commands

## Check filters

- Artisan Command: `api:check-filter`
    - Arguments
        - `filter`
            - The filter you want to check.
    - Options:
        - `--route`
            - Check if your filter would compile to a route.

Because the [Filters](#filters) are not the easiest to understand we have you covered with the `check-filter` command. This command will check try to parse the provided filter and display them in a
nice and neat array.

Using the `--route` option you can check if your filter would have been valid if it were used as a [Route filter](#route-filters)

### Example output

```shell
php artisan api:check-filter 'foo:*bar:baz* moo:cow'

> +------+--------+
> | Type | Filter |
> +------+--------+
> | foo  | *bar   |
> | moo  | cow    |
> +------+--------+
```

## Export a Swagger config file

- Artisan Command: `api:swagger`
- Options:
    - [`--T|title=`](#information)
        - Add a title to your Swagger config
    - [`--D|description=`](#information)
        - Add a description to your Swagger config
    - [`--set-version=`](#information)
        - Sets the version off the Swagger config
    - [`--O|out=swagger.yml`](#changing-the-output-file)
        - The output path, can be both relative and the full path
    - [`--s|server=*`](#adding-servers-to-your-export)
        - Servers to add, provide the URLs
    - [`--t|tag=*`](#tagging-routes)
        - Tag a part of your endpoints using a specified syntax
    - [`--f|filter=*`](#filtering-routes)
        - Filter a part of your endpoints using the filter syntax
    - [`--e|exclude=*`](#excluding-routes)
        - Exclude a part of your endpoints using the filter syntax

### Information

To add information like the `title`, `version` or a `description` to your Swagger config. You can provide the respective options. Sadly Laravel doesn't allow me to use `--version` to set the version (
yet). So you'll have to use `--set-version`.

**Keep in mind that Swagger expects your version to be semantic, meaning that it requires you to make your version comply with that standard.**

```shell
php artisan api:swagger --title='Example.com Swagger'  --description='Example.com Swagger config description' --set-version=1.0.0
#php artisan api:swagger --T 'Example.com Swagger'  -D 'Example.com Swagger config description'
```

### Changing the output file

You can change the output path of the Swagger config using the `--out` or `-O` option. It can receive both a relative and full path and defaults to `swagger.yml`. Meaning it would create the Swagger
config in the current directory in the `swagger.yml` file.

```shell
php artisan api:swagger --out=relative/directory/swagger.yml
#php artisan api:swagger -O /full/path/to/directory/swagger.yml
```

#### JSON export instead of YAML

When you provide the `--out` with a file-path ending with `.json` you will receive a JSON export of the Swagger config instead of a YAML export.

```shell
php artisan api:swagger --out=swagger.json
#php artisan api:swagger -O swagger.json
```

### Adding servers to your export

To add servers; for example your Production / Test environments. You can provide the `--server` option (multiple times if needed);

```shell
php artisan api:swagger --server=test.example.com -s example.com
```

#### Adding a description to your server

To give your server a description use a space in the server definition like below. It only checks for the first space, so after that you can keep adding more spaces.

```shell
php artisan api:swagger --server='test.example.com Test environment' -s 'example.com Production'
```

### "Including" routes

You can include routes using one or more [filter(s)](#filters). Which you can add using the `--include` option (or the `-e` shorthand). For example; When executing the command below we include all
routes with URI's containing `foo` and all routes that use the `api` middleware

```shell
php artisan api:swagger --include='uri:*foo*' -i 'middleware:api'
```

### Excluding routes

You can exclude routes using one or more [filter(s)](#filters). Which you can add using the `--exclude` option (or the `-e` shorthand). For example; When executing the command below we exclude all
routes with URI's containing `foo` and all routes that use the `api` middleware

```shell
php artisan api:swagger --exclude='uri:*foo*' -e 'middleware:api'
```

### Tagging routes

You can tag routes using a _tag name_ and one or more [filter(s)](#filters). You provide these separated by a semicolon (`;`). Resulting in `{tag name}; {filter(s)}` as shown below.

```shell
php artisan api:swagger --tag "Foo; uri:*foo* middleware:'auth:api'"
```

Filters of the same type will always be compared using an OR operation (`||`), whilst having filters of different types will always result in them having to all be met.

So when having 1 middleware filter and 2 URI filters; Your route will need to match the _middleware filter_ and **one of** the _URI filters_.

# Filters

The basics of the filters are easy to understand; they are built up from a `type` and a `filter`. The `Filter::extract($filterInput)` method returns an `array` containing possibly multiple `Filter`
-objects. It does this when the provided `$filterInput` contains multiple filters.

If your filter contains a colon (`:`) you can simply surround your `filter` part of the `$filterInput` with either single (`'`) or double quotes (`"`).

Example:

```php
use Rico\Swagger\Support\Filter;
Filter::extract("foo:bar* lorem_ipsum:*lorem*ipsum* want:'colons:too*?'");
// Returns an array with two filters.
//     [ Filter{ type=foo, filter=bar* }, Filter{type=lorem_ipsum, filter=*lorem*ipsum*} ]
```

## Route filters

Route filters are filters that have restricted types. Currently, we only allow filtering using the types below.

- `action` or `a`
    - Filters whatever `$route->getAction()` returns (Controller, Controller@method, "Closure", etc.)
- `middleware` or `m`
    - Filters all the middlewares that the route returns when `$route->gatherMiddlewares()` is called.
- `uri` or `url` or `endpoint` or `e` or `u`
    - Matches `$route->uri()` prefixed with a `'/'` 