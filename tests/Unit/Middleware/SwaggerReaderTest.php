<?php

namespace RicoNijeboer\Swagger\Tests\Unit\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Mockery\MockInterface;
use RicoNijeboer\Swagger\Actions\ReadRouteInformationAction;
use RicoNijeboer\Swagger\Middleware\SwaggerReader;
use RicoNijeboer\Swagger\Support\ValidatorFactory;
use RicoNijeboer\Swagger\Tests\TestCase;

/**
 * Class SwaggerReaderTest
 *
 * @package RicoNijeboer\Swagger\Tests\Unit\Middleware
 */
class SwaggerReaderTest extends TestCase
{
    /**
     * @test
     */
    public function it_registers_an_on_validate_handler_when_enabled()
    {
        /** @var SwaggerReader $middleware */
        $middleware = resolve(SwaggerReader::class);

        $mockValidator = $this->mock(ValidatorFactory::class, function (MockInterface $mock) {
            $mock->shouldReceive('onValidate')
                ->once()
                ->andReturnUndefined();
        });
        $this->app->singleton('validator', fn () => $mockValidator);
        $request = new Request();
        $request->setRouteResolver(fn () => Route::get('index', fn () => response()->noContent()));

        $middleware->handle($request, fn () => response()->noContent());
    }

    /**
     * @test
     */
    public function it_keeps_track_of_all_the_executed_rules()
    {
        $response = response()->noContent();
        $request = new Request();
        $action = function () use ($response) {
            Validator::make(['email' => 'rico@riconijeboer.nl'], ['email' => ['email', 'required']]);
            Validator::make(['email' => 'rico@riconijeboer.nl'], ['name' => ['nullable']]);

            return $response;
        };
        $request->setRouteResolver(fn () => Route::get('index', $action));

        /** @var ReadRouteInformationAction $readAction */
        $readAction = $this->mock(
            ReadRouteInformationAction::class,
            function (MockInterface $mock) use ($response, $request) {
                $mock->shouldReceive('read')
                    ->once()
                    ->withSomeOfArgs(
                        [
                            'email' => ['email', 'required'],
                            'name'  => ['nullable'],
                        ]
                    );
            }
        );
        $middleware = new SwaggerReader($readAction);

        $middleware->handle($request, $action);
    }

    /**
     * @test
     */
    public function it_keeps_track_of_all_the_executed_rules_separately()
    {
        $response = response()->noContent();
        $request = new Request();
        $action = function () use ($response) {
            Validator::make(['email' => 'rico@riconijeboer.nl'], ['email' => 'email|required']);

            return $response;
        };
        $request->setRouteResolver(fn () => Route::get('index', $action));

        /** @var ReadRouteInformationAction $readAction */
        $readAction = $this->mock(
            ReadRouteInformationAction::class,
            function (MockInterface $mock) use ($response, $request) {
                $mock->shouldReceive('read')
                    ->once()
                    ->withSomeOfArgs(
                        [
                            'email' => ['email', 'required'],
                        ]
                    );
            }
        );
        $middleware = new SwaggerReader($readAction);

        $middleware->handle($request, $action);
    }
}
