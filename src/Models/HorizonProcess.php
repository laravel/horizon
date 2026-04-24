<?php

namespace Laravel\Horizon\Models;

use Illuminate\Database\Eloquent\Model;

class HorizonProcess extends Model
{
    protected $guarded = [];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];
}
