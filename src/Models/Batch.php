<?php

namespace RicoNijeboer\Swagger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use RicoNijeboer\Swagger\Database\Factories\BatchFactory;
use RicoNijeboer\Swagger\Models\Contracts\Model;

/**
 * Class Documentation
 *
 * @package RicoNijeboer\Swagger\Models
 * @property string             $route_method
 * @property string             $route_uri
 * @property string             $route_name
 * @property array              $route_middleware
 * @property Collection|Entry[] $entries
 * @method static BatchFactory factory(...$parameters)
 * @method static Builder forRequest(Request $request)
 */
class Batch extends Model
{
    use HasFactory;

    protected $casts = [
        'route_middleware' => 'array',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    /**
     * @param Builder $query
     * @param Request $request
     *
     * @return Builder
     */
    public function scopeForRequest(Builder $query, Request $request): Builder
    {
        $route = $request->route();

        return $query->where('route_method', '=', strtoupper($request->getMethod()))
            ->where('route_uri', '=', $route->uri())
            ->where('route_name', '=', $route->getName())
            ->where('route_middleware', '=', json_encode($route->gatherMiddleware()));
    }
}
