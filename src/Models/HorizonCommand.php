<?php

namespace Laravel\Horizon\Models;

use Illuminate\Database\Eloquent\Model;

class HorizonCommand extends Model
{
    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
    ];
}
