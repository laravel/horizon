<?php

namespace Laravel\Horizon\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Horizon\Enums\JobStatus;

class HorizonJob extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = [];

    protected $casts = [
        'status' => JobStatus::class,
        'failed_at' => 'datetime',
        'completed_at' => 'datetime',
        'reserved_at' => 'datetime',
        'expires_at' => 'datetime',
        'delay' => 'integer',
        'retried_by' => 'array',
    ];
}
