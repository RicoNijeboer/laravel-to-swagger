<?php

namespace RicoNijeboer\Swagger\Models;

use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RicoNijeboer\Swagger\Models\Contracts\Model;

/**
 * Class Entry
 *
 * @package RicoNijeboer\Swagger\Models
 * @property string      $type
 * @property ArrayObject $content
 * @property Batch       $batch
 */
class Entry extends Model
{
    use HasFactory;

    const TYPE_VALIDATION_RULES = 'validation/rules';
    const TYPE_RESPONSE = 'response';

    protected $casts = [
        'content' => AsArrayObject::class,
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'swagger_batch_id');
    }
}
