<?php

namespace RicoNijeboer\Swagger\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
 */
class Batch extends Model
{
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    public function setRouteMiddlewareAttribute(array $route): void
    {
        $this->attributes['route_middleware'] = json_encode($route);
    }

    public function getRouteMiddlewareAttribute(string $serialized): array
    {
        return json_decode($serialized, true);
    }
}
