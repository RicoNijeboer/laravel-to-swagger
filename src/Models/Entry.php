<?php

namespace RicoNijeboer\Swagger\Models;

use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RicoNijeboer\Swagger\Database\Factories\EntryFactory;
use RicoNijeboer\Swagger\Models\Contracts\Model;

/**
 * Class Entry
 *
 * @package RicoNijeboer\Swagger\Models
 * @property string            $type
 * @property ArrayObject|array $content
 * @property Batch             $batch
 * @method static EntryFactory factory(...$parameters)
 */
class Entry extends Model
{
    use HasFactory;

    const TYPE_VALIDATION_RULES = 'validation/rules';
    const TYPE_ROUTE_PARAMETERS = 'route/parameters';
    const TYPE_ROUTE_MIDDLEWARE = 'route/middleware';
    const TYPE_ROUTE_WHERES = 'route/wheres';
    const TYPE_RESPONSE = 'response';

    protected $casts = [
        'content' => AsArrayObject::class,
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}
