<?php

namespace RicoNijeboer\Swagger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;
use RicoNijeboer\Swagger\Database\Factories\BatchFactory;
use RicoNijeboer\Swagger\Models\Contracts\Model;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Class Documentation
 *
 * @package RicoNijeboer\Swagger\Models
 * @property int                $response_code
 * @property string             $route_method
 * @property string             $route_uri
 * @property string             $route_name
 * @property array              $route_middleware
 * @property Collection|Entry[] $entries
 * @property Entry              $validationRulesEntry
 * @property Entry              $responseEntry
 * @property Collection|Tag[] $tags
 * @method static BatchFactory factory(...$parameters)
 * @method static Builder forRequestAndResponse(Request $request, SymfonyResponse $response)
 */
class Batch extends Model
{
    use HasFactory;

    protected $casts = [
        'response_code'    => 'int',
        'route_middleware' => 'array',
    ];

    /**
     * @return HasMany
     */
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    /**
     * @return BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'swagger_batch_tag');
    }

    /**
     * @return HasOne
     */
    public function validationRulesEntry(): HasOne
    {
        return $this->hasOne(Entry::class)
            ->where('type', '=', Entry::TYPE_VALIDATION_RULES);
    }

    /**
     * @return HasOne
     */
    public function responseEntry(): HasOne
    {
        return $this->hasOne(Entry::class)
            ->where('type', '=', Entry::TYPE_RESPONSE);
    }

    /**
     * @param Builder         $query
     * @param Request         $request
     * @param SymfonyResponse $response
     *
     * @return Builder
     */
    public function scopeForRequestAndResponse(Builder $query, Request $request, SymfonyResponse $response): Builder
    {
        $route = $request->route();

        return $query
            ->where('response_code', '=', $response->getStatusCode())
            ->where('route_method', '=', strtoupper($request->getMethod()))
            ->where('route_uri', '=', $route->uri())
            ->where('route_name', '=', $route->getName())
            ->where('route_middleware', '=', json_encode($route->gatherMiddleware()));
    }
}
