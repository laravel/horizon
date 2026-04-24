<?php

namespace Laravel\Horizon\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class HorizonTag extends Model
{
    use Prunable;

    public int $prunableChunkSize = 1000;

    protected $guarded = [];

    protected $casts = [
        'score' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function prunable()
    {
        return static::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', CarbonImmutable::now());
    }
}
