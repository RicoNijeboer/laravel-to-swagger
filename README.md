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
