<?php

namespace Laravel\Horizon\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Laravel\Horizon\Enums\JobStatus;

class HorizonJob extends Model
{
    use Prunable;

    public $incrementing = false;

    public int $prunableChunkSize = 1000;

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

    public function prunable()
    {
        return static::query()->where('expires_at', '<', CarbonImmutable::now());
    }
}
