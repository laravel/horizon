<?php

namespace Laravel\Horizon\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Laravel\Horizon\Enums\SupervisorStatus;

class HorizonSupervisor extends Model
{
    use Prunable;

    public $incrementing = false;

    public int $prunableChunkSize = 1000;

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

    public function prunable()
    {
        return static::query()->where('expires_at', '<', CarbonImmutable::now());
    }
}
