<?php

namespace Laravel\Horizon\Models;

use Illuminate\Database\Eloquent\Model;

class HorizonLock extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
