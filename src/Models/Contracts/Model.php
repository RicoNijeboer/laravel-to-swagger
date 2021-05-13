<?php

namespace RicoNijeboer\Swagger\Models\Contracts;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Support\Carbon;

/**
 * Class Model
 *
 * @package RicoNijeboer\Swagger\Models\Contracts
 * @property-read int    $id
 * @property-read Carbon $created_at
 * @property Carbon      $updated_at
 */
abstract class Model extends BaseModel
{
    public function getTable()
    {
        return $this->table ?? 'swagger_' . parent::getTable();
    }
}
