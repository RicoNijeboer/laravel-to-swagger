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

        $oAuthSchemes = $action->oAuth2();

        $this->assertNotNull($oAuthSchemes);
        $this->assertArrayHasKeys(
            [
                'api.type' => 'oauth2',
                'api.flows',
            ],
            $oAuthSchemes
        );
        $this->assertIsArray($oAuthSchemes['api']['flows']);
        $this->assertEmpty($oAuthSchemes['api']['flows']);
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

        $oauthSchemes = $action->oAuth2();

        $this->assertArrayHasKeys(
            [
                'api.type' => 'oauth2',
                'api.flows.password.tokenUrl',
                'api.flows.password.scopes',
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

        $oauthSchemes = $action->oAuth2();

        $this->assertArrayHasKeys(
            [
                'api.type' => 'oauth2',
                'api.flows.clientCredentials.tokenUrl',
                'api.flows.clientCredentials.scopes',
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

        $oauthSchemes = $action->oAuth2();

        $this->assertArrayHasKeys(
            [
                'api.type' => 'oauth2',
                'api.flows.implicit.tokenUrl',
                'api.flows.implicit.authorizationUrl',
                'api.flows.implicit.scopes',
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
        Passport::enableImplicitGrant();
        /** @var ClientRepository $clientRepository */
        $clientRepository = resolve(ClientRepository::class);
        $clientRepository->create(
            null, 'Auth code client', URL::to('/auth/callback')
        );

        $oauthSchemes = $action->oAuth2();

        $this->assertArrayHasKeys(
            [
                'api.type' => 'oauth2',
                'api.flows.authorizationCode.tokenUrl',
                'api.flows.authorizationCode.authorizationUrl',
                'api.flows.authorizationCode.scopes',
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

        $oauthSchemes = $action->oAuth2();

        $this->assertArrayHasKeys(
            [
                'api.flows.password.scopes.Scope'  => 'Scope it all',
                'api.flows.password.scopes.Scope2' => 'Electric boogaloo',
            ],
            $oauthSchemes
        );
    }
}
