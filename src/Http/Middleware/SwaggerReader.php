<?php

namespace RicoNijeboer\Swagger\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationRuleParser;
use RicoNijeboer\Swagger\Actions\ReadRouteInformationAction;
use RicoNijeboer\Swagger\Http\Middleware\Concerns\AttachesTagsToBatches;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SwaggerReader
 *
 * @package RicoNijeboer\Swagger\Middleware
 */
class SwaggerReader
{
    use AttachesTagsToBatches;

    protected array $rules = [];

    protected ReadRouteInformationAction $readRouteInformationAction;

    /** @var string[] */
    protected array $tags = [];

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
     * @param Request         $request
     * @param Closure         $next
     * @param string[]|string ...$tags
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$tags)
    {
        $this->rules = [];
        $this->tags = $tags;

        Validator::onValidate(function (array $addedRules, array $data = []) {
            $parsed = (new ValidationRuleParser($data))->explode($addedRules);

            $this->rules = array_merge_recursive($this->rules, $parsed->rules);
        });

        return $next($request);
    }

    public function terminate(Request $request, Response $response)
    {
        $shouldEvaluate = $this->shouldEvaluate($request, $response);

        if ($shouldEvaluate) {
            $this->deleteExistingBatch($request, $response);
            $this->read($request, $response, $this->rules);
        }

        $this->attachTags($request, $response, $this->tags);
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
     * @param Request  $request
     * @param Response $response
     *
     * @return bool
     */
    protected function shouldEvaluate(Request $request, Response $response): bool
    {
        return $this->batchQuery($request, $response)->doesntExist();
    }

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return void
     */
    protected function deleteExistingBatch(Request $request, Response $response): void
    {
        $this->batchQuery($request, $response, false)->delete();
    }
}
