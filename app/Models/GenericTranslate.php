<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GenericTranslate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GenericTranslate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GenericTranslate query()
 * @mixin \Eloquent
 */
class GenericTranslate extends Model
{
    public $timestamps = true;

    protected $attributes = [];

    protected $guarded = [];

    protected $casts = [
        'id' => 'integer',
        'created_at' => 'datetime:d-m-Y H:i:s',
    ];
}
