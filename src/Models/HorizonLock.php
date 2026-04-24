<?php

namespace Laravel\Horizon\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class HorizonLock extends Model
{
    use Prunable;

    public $incrementing = false;

    public int $prunableChunkSize = 1000;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function prunable()
    {
        return static::query()->where('expires_at', '<', CarbonImmutable::now());
    }
}
