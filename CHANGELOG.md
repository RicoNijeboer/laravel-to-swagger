# Changelog

All notable changes will be documented here.

## v2.3.0 - 2021-08-24

### Changes

- An example response body is now added for `application/json` responses. This body is completely anonymous, but is more representative of your actual response.
- Security schemes are now applied to paths
    - When you use `laravel/passport` and your route requires certain `scopes` it also adds these to your path.

**Bugfixes**

- Fixed a bug in the ValueHelper::jsonResponseProperty where it failed when the `$value` given is an empty array
  - https://github.com/RicoNijeboer/laravel-to-swagger/issues/21
- It now only loads batches it can display
  - https://github.com/RicoNijeboer/laravel-to-swagger/issues/22

## v2.2.1 - 2021-05-19

**Bugfixes**

- When you have multiple routes on the same uri with different methods, it now displays all possible methods.
- It stores tags correctly (based on the documentation) again.

## v2.2.0 - 2021-05-15

### Changes

- When a field in your request validation has a [`regex`](https://laravel.com/docs/8.x/validation#rule-regex) rule. It gets a `format` rule within the Swagger config.
- When a route-parameter has a [`where`](https://laravel.com/docs/8.x/routing#parameters-regular-expression-constraints) (or `format` in Swagger terms) it now adds this in the Swagger config.
- Added support for [server templating](https://github.com/RicoNijeboer/laravel-to-swagger/issues/4).
- OAuth URLs are now relative when you don't supply a custom domain to them.
- When a route has a custom domain using `->domain(...)` it is now displayed in the Swagger config.
- The `SwaggerReader` and `SwaggerTag` are now [Terminable Middlewares](https://laravel.com/docs/8.x/middleware#terminable-middleware), which means your users should feel even less of an impact when
  using your app

### Upgrading

- When updating to this version add an alter migration for the `swagger_batches` table which adds a nullable `route_domain` string-column   
  `$table->string('route_domain')->nullable();`

---

## v2.1.3 - 2021-05-17

**Bugfixes**

- `cascadeOnDelete` instead of `onDeleteCascade` because the second one does not exist...

---

## v2.1.2 - 2021-05-15

**Bugfixes**

- The configured connection was not being used within the added migrations.
    - To ensure your tables are created in the right connection add a `connection` call to the `Schema::create` in the migration.    
      `Schema::connection(config('swagger.database.connection'))`

---

## v2.1.1 - 2021-05-15

**Bugfixes**

- Fixed a bug where existing batches without a parameter entry would make your config get corrupted.

---

## v2.1.0 - 2021-05-15

**Features**

- Added Swagger UI option

**Bugfixes**

- Fixed a bug with foreign key constraints not cascade deleting

---

## v2.0.0 - 2021-05-14

- Responses are now calculated and sent to the config as well.

---

## Changes since version 1

When version 1 would create a Swagger config, it generated it by reading all the registered Routes that match the filters (defined by the developers). This worked fine, except there were a few hacks I
had to do to not mess up any data sources you had in your database.

With version 2 this is a lot different; I don't execute your code based on the registered routes. You execute your code, and in the background I store just enough information to generate a Swagger
config (with responses now 🎉). Because I want you to have safe data in your database, I completely obfuscate the json responses you are sending from your API. I have tried doing so and keeping the
same data formats, although I may have missed one.
(If you find one feel free to make a Pull Request or Issue, and I'll get to it).

With version 2 gone are all the filters that you had to define; You add the middleware and tag through the middleware. That's it.
