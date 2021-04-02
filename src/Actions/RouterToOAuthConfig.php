<?php

namespace Rico\Swagger\Actions;

use Illuminate\Routing\Router;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Laravel\Passport\Scope;

/**
 * Class RouterToOAuthConfig
 *
 * @package Rico\Swagger\Actions
 */
class RouterToOAuthConfig
{
    /**
     * Create OAuth config based on Laravel Passport
     *
     * @param Router $router
     *
     * @return mixed[]
     */
    public function laravelPassport(Router $router): array
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
     * @return bool
     */
    protected function hasLaravelPassport(): bool
    {
        return count(app()->getProviders('\Laravel\Passport\PassportServiceProvider')) !== 0;
    }
}