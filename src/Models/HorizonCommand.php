<?php

namespace Laravel\Horizon\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class HorizonCommand extends Model
{
    use Prunable;

    public int $prunableChunkSize = 1000;

    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
    ];

    public function prunable()
    {
        return static::query()->where('created_at', '<', CarbonImmutable::now()->subDay());
    }
}
