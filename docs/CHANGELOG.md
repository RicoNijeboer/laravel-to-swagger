# Changelog

All notable changes will be documented here.

## v2.1.1 - 2021-05-15

**Bugfixes**

- Fixed a bug where existing batches without a parameter entry would make your config get corrupted.

## v2.1.0 - 2021-05-15

**Features**

- Added Swagger UI option

**Bugfixes**

- Fixed a bug with foreign key constraints not cascade deleting

## v2.0.0 - 2021-05-14

- Responses are now calculated and sent to the config as well.

## Changes since version 1

When version 1 would create a Swagger config, it generated it by reading all the registered Routes that match the filters (defined by the developers). This worked fine, except there were a few hacks I
had to do to not mess up any data sources you had in your database.

With version 2 this is a lot different; I don't execute your code based on the registered routes. You execute your code, and in the background I store just enough information to generate a Swagger
config (with responses now 🎉). Because I want you to have safe data in your database, I completely obfuscate the json responses you are sending from your API. I have tried doing so and keeping the
same data formats, although I may have missed one.
(If you find one feel free to make a Pull Request or Issue, and I'll get to it).

With version 2 gone are all the filters that you had to define; You add the middleware and tag through the middleware. That's it.