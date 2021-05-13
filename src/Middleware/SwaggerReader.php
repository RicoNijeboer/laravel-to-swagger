<?php

namespace RicoNijeboer\Swagger\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationRuleParser;
use RicoNijeboer\Swagger\Actions\ReadRouteInformationAction;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SwaggerReader
 *
 * @package RicoNijeboer\Swagger\Middleware
 */
class SwaggerReader
{
    private ReadRouteInformationAction $readRouteInformationAction;

    public function __construct(ReadRouteInformationAction $readRouteInformationAction)
    {
        $this->readRouteInformationAction = $readRouteInformationAction;
    }

    public function handle(Request $request, Closure $next)
    {
        $swaggerEnabled = true;
        $rules = [];

        if ($swaggerEnabled) {
            Validator::onValidate(function (array $addedRules, array $data = []) use (&$rules) {
                $parsed = (new ValidationRuleParser($data))->explode($addedRules);

                $rules = array_merge_recursive(
                    $rules, $parsed->rules
                );
            });
        }

        $response = $next($request);

        if ($swaggerEnabled) {
            $this->read($request, $response, $rules);
        }

        return $response;
    }

    protected function read(Request $request, Response $result, array $rules = []): void
    {
        $this->readRouteInformationAction->read($request, $request->route(), $result, $rules);
    }
}
