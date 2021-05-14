<?php

namespace RicoNijeboer\Swagger\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use RicoNijeboer\Swagger\Http\Middleware\Concerns\AttachesTagsToBatches;

/**
 * Class SwaggerTag
 *
 * @package RicoNijeboer\Swagger\Http\Middleware
 */
class SwaggerTag
{
    use AttachesTagsToBatches;

    /**
     * @param Request         $request
     * @param Closure         $next
     * @param string|string[] ...$tags
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$tags)
    {
        $response = $next($request);

        $this->attachTags($request, $response, $tags, true);

        return $response;
    }
}
