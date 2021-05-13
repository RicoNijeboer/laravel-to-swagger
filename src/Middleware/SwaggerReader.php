<?php

namespace RicoNijeboer\Swagger\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationRuleParser;
use RicoNijeboer\Swagger\Actions\ReadRouteInformationAction;
use RicoNijeboer\Swagger\Models\Batch;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SwaggerReader
 *
 * @package RicoNijeboer\Swagger\Middleware
 */
class SwaggerReader
{
    protected ReadRouteInformationAction $readRouteInformationAction;

    /**
     * SwaggerReader constructor.
     *
     * @param ReadRouteInformationAction $readRouteInformationAction
     */
    public function __construct(ReadRouteInformationAction $readRouteInformationAction)
    {
        $this->readRouteInformationAction = $readRouteInformationAction;
    }

    /**
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $swaggerEnabled = $this->shouldEvaluate($request);
        $rules = [];

        if ($swaggerEnabled) {
            Validator::onValidate(function (array $addedRules, array $data = []) use (&$rules) {
                $parsed = (new ValidationRuleParser($data))->explode($addedRules);

                $rules = array_merge_recursive($rules, $parsed->rules);
            });
        }

        $response = $next($request);

        if ($swaggerEnabled) {
            $this->deleteExistingBatch($request);
            $this->read($request, $response, $rules);
        }

        return $response;
    }

    /**
     * @param Request  $request
     * @param Response $result
     * @param string[] $rules
     *
     * @return void
     */
    protected function read(Request $request, Response $result, array $rules = []): void
    {
        $this->readRouteInformationAction->read($request, $request->route(), $result, $rules);
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    protected function shouldEvaluate(Request $request): bool
    {
        return $this->batchQuery($request)->doesntExist();
    }

    /**
     * @param Request $request
     * @param bool    $between
     *
     * @return Builder
     */
    protected function batchQuery(Request $request, bool $between = true): Builder
    {
        $delay = config('swagger.evaluation-delay', 43200);

        return Batch::forRequest($request)
            ->{$between ? 'whereBetween' : 'whereNotBetween'}('updated_at', [now()->subSeconds($delay), now()]);
    }

    /**
     * @param Request $request
     *
     * @return void
     */
    protected function deleteExistingBatch(Request $request): void
    {
        $this->batchQuery($request, false)->delete();
    }
}
