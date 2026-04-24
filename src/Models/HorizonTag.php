<?php

namespace Laravel\Horizon\Models;

use Illuminate\Database\Eloquent\Model;

class HorizonTag extends Model
{
    protected $guarded = [];

    protected $casts = [
        'score' => 'float',
        'expires_at' => 'datetime',
    ];
}
