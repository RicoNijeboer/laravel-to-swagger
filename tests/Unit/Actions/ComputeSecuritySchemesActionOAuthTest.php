<?php

namespace RicoNijeboer\Swagger\Tests\Unit\Actions;

use Illuminate\Support\Facades\URL;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\RouteRegistrar;
use RicoNijeboer\Swagger\Actions\ComputeSecuritySchemesAction;
use RicoNijeboer\Swagger\Tests\TestCase;

/**
 * Class ComputeSecuritySchemes
 *
 * @package RicoNijeboer\Swagger\Tests\Unit\Actions
 */
class ComputeSecuritySchemesActionOAuthTest extends TestCase
{
    /**
     * @test
     */
    public function it_does_not_return_any_flows_when_there_are_none_installed()
    {
        /** @var ComputeSecuritySchemesAction $action */
        $action = resolve(ComputeSecuritySchemesAction::class);
        config()->set('auth.guards', [
            'api' => [
                'driver'   => 'passport',
                'provider' => 'users',
            ],
        ]);
        Passport::$implicitGrantEnabled = false;
        Passport::client()->delete();

        $oAuthSchemes = $action->oAuth2Schemes();

        $this->assertNotNull($oAuthSchemes);
        $this->assertArrayHasKeys(
            [
                'Guard: api.type' => 'oauth2',
                'Guard: api.flows',
            ],
            $oAuthSchemes
        );
        $this->assertIsArray($oAuthSchemes['Guard: api']['flows']);
        $this->assertEmpty($oAuthSchemes['Guard: api']['flows']);
    }

    /**
     * @test
     */
    public function it_returns_the_password_flow_when_it_is_enabled()
    {
        /** @var ComputeSecuritySchemesAction $action */
        $action = resolve(ComputeSecuritySchemesAction::class);
        config()->set('auth.guards', [
            'api' => [
                'driver'   => 'passport',
                'provider' => 'users',
            ],
        ]);
        Passport::routes(fn (RouteRegistrar $passport) => $passport->forAccessTokens());
        /** @var ClientRepository $clientRepository */
        $clientRepository = resolve(ClientRepository::class);
        $clientRepository->createPasswordGrantClient(null, 'Password client', 'http://localhost', 'users');

        $oauthSchemes = $action->oAuth2Schemes();

        $this->assertArrayHasKeys(
            [
                'Guard: api.type' => 'oauth2',
                'Guard: api.flows.password.tokenUrl',
                'Guard: api.flows.password.scopes',
            ],
            $oauthSchemes
        );
    }

    /**
     * @test
     */
    public function it_returns_the_client_credentials_flow_when_it_is_enabled()
    {
        /** @var ComputeSecuritySchemesAction $action */
        $action = resolve(ComputeSecuritySchemesAction::class);
        config()->set('auth.guards', [
            'api' => [
                'driver'   => 'passport',
                'provider' => 'users',
            ],
        ]);
        Passport::routes(fn (RouteRegistrar $passport) => $passport->forAccessTokens());
        /** @var ClientRepository $clientRepository */
        $clientRepository = resolve(ClientRepository::class);
        $clientRepository->create(null, 'Client Credentials Client', '');

        $oauthSchemes = $action->oAuth2Schemes();

        $this->assertArrayHasKeys(
            [
                'Guard: api.type' => 'oauth2',
                'Guard: api.flows.clientCredentials.tokenUrl',
                'Guard: api.flows.clientCredentials.scopes',
            ],
            $oauthSchemes
        );
    }

    /**
     * @test
     */
    public function it_returns_the_implicit_flow_when_it_is_enabled()
    {
        /** @var ComputeSecuritySchemesAction $action */
        $action = resolve(ComputeSecuritySchemesAction::class);
        config()->set('auth.guards', [
            'api' => [
                'driver'   => 'passport',
                'provider' => 'users',
            ],
        ]);
        Passport::routes();
        Passport::enableImplicitGrant();

        $oauthSchemes = $action->oAuth2Schemes();

        $this->assertArrayHasKeys(
            [
                'Guard: api.type' => 'oauth2',
                'Guard: api.flows.implicit.tokenUrl',
                'Guard: api.flows.implicit.authorizationUrl',
                'Guard: api.flows.implicit.scopes',
            ],
            $oauthSchemes
        );
    }

    /**
     * @test
     */
    public function it_returns_the_authorization_code_flow_when_it_is_enabled()
    {
        /** @var ComputeSecuritySchemesAction $action */
        $action = resolve(ComputeSecuritySchemesAction::class);
        config()->set('auth.guards', [
            'api' => [
                'driver'   => 'passport',
                'provider' => 'users',
            ],
        ]);
        Passport::routes();
        /** @var ClientRepository $clientRepository */
        $clientRepository = resolve(ClientRepository::class);
        $clientRepository->create(
            null, 'Auth code client', URL::to('/auth/callback')
        );

        $oauthSchemes = $action->oAuth2Schemes();

        $this->assertArrayHasKeys(
            [
                'Guard: api.type' => 'oauth2',
                'Guard: api.flows.authorizationCode.tokenUrl',
                'Guard: api.flows.authorizationCode.authorizationUrl',
                'Guard: api.flows.authorizationCode.scopes',
            ],
            $oauthSchemes
        );
    }

    /**
     * @test
     */
    public function it_returns_all_possible_scopes()
    {
        /** @var ComputeSecuritySchemesAction $action */
        $action = resolve(ComputeSecuritySchemesAction::class);
        config()->set('auth.guards', [
            'api' => [
                'driver'   => 'passport',
                'provider' => 'users',
            ],
        ]);
        Passport::routes(fn (RouteRegistrar $passport) => $passport->forAccessTokens());
        /** @var ClientRepository $clientRepository */
        $clientRepository = resolve(ClientRepository::class);
        $clientRepository->createPasswordGrantClient(null, 'Password client', 'http://localhost', 'users');

        Passport::tokensCan([
            'Scope'  => 'Scope it all',
            'Scope2' => 'Electric boogaloo',
        ]);

        $oauthSchemes = $action->oAuth2Schemes();

        $this->assertArrayHasKeys(
            [
                'Guard: api.flows.password.scopes.Scope'  => 'Scope it all',
                'Guard: api.flows.password.scopes.Scope2' => 'Electric boogaloo',
            ],
            $oauthSchemes
        );
    }

    /**
     * @test
     */
    public function it_returns_the_urls_relatively()
    {
        /** @var ComputeSecuritySchemesAction $action */
        $action = resolve(ComputeSecuritySchemesAction::class);
        config()->set('auth.guards', [
            'api' => [
                'driver'   => 'passport',
                'provider' => 'users',
            ],
        ]);
        Passport::routes();
        Passport::enableImplicitGrant();
        /** @var ClientRepository $clientRepository */
        $clientRepository = resolve(ClientRepository::class);
        $clientRepository->create(
            null, 'Auth code client', URL::to('/auth/callback')
        );
        $clientRepository->create(null, 'Client Credentials Client', '');
        $clientRepository->createPasswordGrantClient(null, 'Password client', 'http://localhost', 'users');

        $schemes = $action->oAuth2Schemes();

        $this->assertArrayHasKeys(
            [
                'Guard: api.flows.password.tokenUrl'                  => '/oauth/token',
                'Guard: api.flows.clientCredentials.tokenUrl'         => '/oauth/token',
                'Guard: api.flows.authorizationCode.tokenUrl'         => '/oauth/token',
                'Guard: api.flows.authorizationCode.authorizationUrl' => '/oauth/authorize',
                'Guard: api.flows.implicit.tokenUrl'                  => '/oauth/token',
                'Guard: api.flows.implicit.authorizationUrl'          => '/oauth/authorize',
            ],
            $schemes
        );
    }

    /**
     * @test
     */
    public function it_returns_full_urls_when_the_routes_have_a_domain()
    {
        /** @var ComputeSecuritySchemesAction $action */
        $action = resolve(ComputeSecuritySchemesAction::class);
        config()->set('auth.guards', [
            'api' => [
                'driver'   => 'passport',
                'provider' => 'users',
            ],
        ]);
        URL::forceScheme('https');
        Passport::routes(null, ['domain' => 'oauth.example.com']);
        Passport::enableImplicitGrant();
        /** @var ClientRepository $clientRepository */
        $clientRepository = resolve(ClientRepository::class);
        $clientRepository->create(null, 'Auth code client', URL::to('/auth/callback'));
        $clientRepository->create(null, 'Client Credentials Client', '');
        $clientRepository->createPasswordGrantClient(null, 'Password client', 'http://localhost', 'users');

        $schemes = $action->oAuth2Schemes();

        $this->assertArrayHasKeys(
            [
                'Guard: api.flows.password.tokenUrl'                  => 'https://oauth.example.com/oauth/token',
                'Guard: api.flows.clientCredentials.tokenUrl'         => 'https://oauth.example.com/oauth/token',
                'Guard: api.flows.authorizationCode.tokenUrl'         => 'https://oauth.example.com/oauth/token',
                'Guard: api.flows.authorizationCode.authorizationUrl' => 'https://oauth.example.com/oauth/authorize',
                'Guard: api.flows.implicit.tokenUrl'                  => 'https://oauth.example.com/oauth/token',
                'Guard: api.flows.implicit.authorizationUrl'          => 'https://oauth.example.com/oauth/authorize',
            ],
            $schemes
        );
    }
}
