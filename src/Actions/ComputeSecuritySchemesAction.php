<?php

namespace RicoNijeboer\Swagger\Actions;

use Generator;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\URL;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Laravel\Passport\Scope;

/**
 * Class ComputeSecuritySchemesAction
 *
 * @package RicoNijeboer\Swagger\Actions
 */
class ComputeSecuritySchemesAction
{
    const PASSPORT_TOKEN_ROUTE_NAME = 'passport.token';
    const PASSPORT_TOKEN_ROUTE_SLUG = '/oauth/token';
    const PASSPORT_AUTHORIZATIONS_AUTHORIZE_ROUTE_NAME = 'passport.authorizations.authorize';
    const PASSPORT_AUTHORIZATIONS_AUTHORIZE_ROUTE_SLUG = '/oauth/authorize';

    private Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function oAuth2Schemes(): ?array
    {
        $schemes = [];
        $guards = $this->guards(fn (array $guard, string $name) => $guard['driver'] === 'passport');

        foreach ($guards as [$guard, $name]) {
            $this->oauthScheme($schemes, $name, $guard);
        }

        if (empty($schemes)) {
            return null;
        }

        return $schemes;
    }

    /**
     * @param callable|null $filter
     *
     * @return Generator
     */
    protected function guards(callable $filter = null): Generator
    {
        $guards = config('auth.guards', []);

        foreach ($guards as $name => $guard) {
            if (is_null($filter) || $filter($guard, $name)) {
                yield [$guard, $name];
            }
        }
    }

    /**
     * @param array  $schemes
     * @param string $name
     * @param array  $guard
     *
     * @return void
     */
    protected function oauthScheme(array &$schemes, string $name, array $guard): void
    {
        $guardName = "Guard: {$name}";

        $hasTokenUrl = $this->router->has(self::PASSPORT_TOKEN_ROUTE_NAME);
        $hasAuthorizationUrl = $this->router->has(self::PASSPORT_AUTHORIZATIONS_AUTHORIZE_ROUTE_NAME);

        $tokenUrl = $hasTokenUrl ? URL::route(self::PASSPORT_TOKEN_ROUTE_NAME)
            : URL::to(self::PASSPORT_TOKEN_ROUTE_SLUG);
        $authorizationUrl = $hasAuthorizationUrl ? URL::route(self::PASSPORT_AUTHORIZATIONS_AUTHORIZE_ROUTE_NAME)
            : URL::to(self::PASSPORT_AUTHORIZATIONS_AUTHORIZE_ROUTE_SLUG);

        $clients = Passport::client()->newQuery()->get();
        $scopes = Passport::scopes()->mapWithKeys(fn (Scope $scope) => [$scope->id => $scope->description])->all();

        $schemes[$guardName] = [
            'type'  => 'oauth2',
            'flows' => [
                'password'          => [
                    'tokenUrl' => $tokenUrl,
                    'scopes'   => $scopes,
                ],
                'clientCredentials' => [
                    'tokenUrl' => $tokenUrl,
                    'scopes'   => $scopes,
                ],
                'authorizationCode' => [
                    'tokenUrl'         => $tokenUrl,
                    'authorizationUrl' => $authorizationUrl,
                    'scopes'           => $scopes,
                ],
                'implicit'          => [
                    'tokenUrl'         => $tokenUrl,
                    'authorizationUrl' => $authorizationUrl,
                    'scopes'           => $scopes,
                ],
            ],
        ];

        $checkEnabled = [
            'password'          => $hasTokenUrl && $clients->filter(fn (Client $client) => $client->getAttribute('password_client'))->isNotEmpty(),
            'clientCredentials' => $hasTokenUrl && $clients->filter(fn (Client $client) => $client->confidential())->isNotEmpty(),
            'authorizationCode' => $hasTokenUrl && $hasAuthorizationUrl && $clients->filter(fn (Client $client) => !$client->firstParty())->isNotEmpty(),
            'implicit'          => Passport::$implicitGrantEnabled,
        ];

        foreach ($checkEnabled as $flow => $enabled) {
            if (!$enabled) {
                unset($schemes[$guardName]['flows'][$flow]);
            }
        }
    }
}
