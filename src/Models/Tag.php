<?php

namespace RicoNijeboer\Swagger\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use RicoNijeboer\Swagger\Database\Factories\TagFactory;
use RicoNijeboer\Swagger\Models\Contracts\Model;

/**
 * Class Tag
 *
 * @package RicoNijeboer\Swagger\Models
 * @property string             $tag
 * @property Collection|Batch[] $batches
 * @method static TagFactory factory(...$parameters)
 */
class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['tag'];

    public function batches(): BelongsToMany
    {
        return $this->belongsToMany(Batch::class, 'swagger_batch_tag');
    }
}
