# Laravel to Swagger

Export your routes to a basic Swagger config the properties of your request (and their data types wherever I can).

# Disclaimer

While exporting a part of your code **will** be ran. I've made sure *no* should go to your database as I've overwritten
the Laravel database connection resolver to always return a connection which throws an Exception I use to determine when
to stop listening for validation errors.

Because of this I **will** not and **do** not take responsibility for any issues resulting from an export created using
this package. Unless of course it directly impacts the functionality of this package.

# Requirements

- This package requires PHP 7.4 or higher.
- Laravel 6 / 7 / 8

# Installation

```shell
composer require riconijeboer/laravel-to-swagger
```

# Commands

## Export a Swagger config file

```
php artisan api:swagger
    {--T|title= : Add a title to your Swagger config}
    {--D|description= : Add a description to your Swagger config}
    {--set-version= : Sets the version off the Swagger config}
    {--O|out=swagger.yml : The output path, can be both relative and the full path}
    {--s|server=* : Servers to add, provide the URLs}
    {--t|tag=* : Tag a part of your endpoints using a specified syntax}
```

### Information

To add information like the `title`, `version` or a `description` to your Swagger config. You can provide the respective
options. Sadly Laravel doesn't allow me to use `--version` to set the version (yet). So you'll have to
use `--set-version`.

**Keep in mind that Swagger expects your version to be semantic, meaning that it requires you to make your version
comply with that standard.**

```shell
php artisan api:swagger --title='Example.com Swagger'  --description='Example.com Swagger config description' --set-version=1.0.0
#php artisan api:swagger --T 'Example.com Swagger'  -D 'Example.com Swagger config description'
```

### JSON export instead of YAML

When you provide the `--out` with a file-path ending with `.json` you will receive a JSON export of the Swagger config
instead of a YAML export.

```shell
php artisan api:swagger --out=swagger.json
#php artisan api:swagger -O swagger.json
```

### Adding servers to your export

To add servers; for example your Production / Test environments. You can provide the `--server` option (multiple times
if needed);

```shell
php artisan api:swagger --server=test.example.com -s example.com
```

#### Adding a description to your server

To give your server a description use a space in the server definition like below. It only checks for the first space,
so after that you can keep adding more spaces.

```shell
php artisan api:swagger --server='test.example.com Test environment' -s 'example.com Production'
```

### Tag

Grouping your requests in Swagger is done using tags. Using the command you can tag your endpoints using the filters
below. Every filter can also be combined for more specific filters!

### Syntax

To tag your endpoints add a `--tag` or `-t` when calling the command. They take a value that is structured as follows;

```text
{Tag Name}; {filters}
```

### Tag Filters

Currently we accept the following filter types all using the same syntax

- Controllers (`c:...`)
- Middlewares (`m:...`)
- Endpoint / Path (`e:...`)

Using 2 controller filters will apply the filter when either one of the filters is met. However if you provide one of
each of the filter types all of the filters have to be met.

#### Controller

You can tag a request based on a controller by giving your tag-filter a `c:` prefix

```shell
# Tag endpoints that use a controller where the name starts with "Order" with "Orders"
php artisan api:swagger --tag="Orders; c:Order*"

# Tag endpoints that use a controller where the name contains "Order" with "Orders"
php artisan api:swagger -t "Orders; c:*Order*"

# Tag endpoints that use a controller where the name contains either "Order" or "Cart" with "Shopping"
php artisan api:swagger -t "Shopping; c:*Order* c:*Cart*"
```

#### Middlewares

You can tag a request based on a controller by giving your tag-filter a `m:` prefix

```shell
# Tag endpoints with "Api" when they use a middleware that either ends with "-api" or is equal to "api"
php artisan api:swagger --tag="Api; m:*-api m:api"
```

#### Endpoint / Path

You can tag a request based on a controller by giving your tag-filter a `e:` prefix

```shell
# Tag endpoints with "Orders" when they exist on a path that contains "order"
php artisan api:swagger --tag="Orders; e:*order*"

# Tag endpoints with "Shopping" when they exist on a path that contains either "product" or "order"
php artisan api:swagger -t "Shopping; e:*product* e:*order*"
```

#### Combining

As said before you can also combine the filters!

```shell
# Tag endpoints with "API Orders" when they exist on a path that contains "order" and they use the "api" middleware
php artisan api:swagger --tag="API Orders; e:*order* m:api"
```



