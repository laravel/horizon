<?php

namespace Laravel\Horizon\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Horizon\Enums\SupervisorStatus;

class HorizonSupervisor extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'name';

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'status' => SupervisorStatus::class,
        'pid' => 'integer',
        'expires_at' => 'datetime',
        'processes' => 'array',
        'options' => 'array',
    ];
}
