<?php

namespace RicoNijeboer\Swagger\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use RicoNijeboer\Swagger\Http\Middleware\Concerns\AttachesTagsToBatches;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SwaggerTag
 *
 * @package RicoNijeboer\Swagger\Http\Middleware
 */
class SwaggerTag
{
    use AttachesTagsToBatches;

    /** @var string[] */
    private array $tags;

    /**
     * @param Request         $request
     * @param Closure         $next
     * @param string|string[] ...$tags
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$tags)
    {
        $this->tags = $tags;

        return $next($request);
    }

    /**
     * @param Request  $request
     * @param Response $response
     */
    public function terminate(Request $request, Response $response)
    {
        $this->attachTags($request, $response, $this->tags, true);
    }
}
