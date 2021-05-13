<?php

namespace RicoNijeboer\Swagger\Providers;

use Illuminate\Validation\ValidationServiceProvider as BaseValidationServiceProvider;
use RicoNijeboer\Swagger\Support\ValidatorFactory;

/**
 * Class ValidationServiceProvider
 *
 * @package RicoNijeboer\Swagger\Providers
 */
class ValidationServiceProvider extends BaseValidationServiceProvider
{
    /**
     * Register the validation factory.
     *
     * @return void
     */
    protected function registerValidationFactory()
    {
        $this->app->singleton('validator', function ($app) {
            $validator = new ValidatorFactory($app['translator'], $app);

            // The validation presence verifier is responsible for determining the existence of
            // values in a given data collection which is typically a relational database or
            // other persistent data stores. It is used to check for "uniqueness" as well.
            if (isset($app['db'], $app['validation.presence'])) {
                $validator->setPresenceVerifier($app['validation.presence']);
            }

            return $validator;
        });
    }
}
