<?php

namespace RicoNijeboer\Swagger\Http\Middleware\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use RicoNijeboer\Swagger\Models\Batch;
use RicoNijeboer\Swagger\Models\Tag;
use Symfony\Component\HttpFoundation\Response;

trait AttachesTagsToBatches
{
    /**
     * @param Request  $request
     * @param Response $response
     * @param bool     $between
     *
     * @return Builder
     */
    protected function batchQuery(Request $request, Response $response, bool $between = true): Builder
    {
        $delay = config('swagger.evaluation-delay', 43200);

        return Batch::forRequestAndResponse($request, $response)
            ->{$between ? 'whereBetween' : 'whereNotBetween'}('updated_at', [now()->subSeconds($delay), now()]);
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param array    $tags
     * @param bool     $merge
     */
    protected function attachTags(Request $request, Response $response, array $tags, bool $merge = false): void
    {
        /** @var Batch $batch */
        $batch = $this->batchQuery($request, $response)->first();

        if (is_null($batch)) {
            return;
        }

        $tags = collect($tags)->map(fn (string $tag) => Tag::query()->firstOrCreate(['tag' => $tag])->getKey());

        if ($merge) {
            $tags->each(fn (int $tagId) => $batch->tags()->attach($tagId));
        } else {
            $batch->tags()->sync($tags);
        }
    }
}
