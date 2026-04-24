<?php

namespace Laravel\Horizon\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Horizon\Enums\MetricKind;

class HorizonMetricIncrement extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'kind' => MetricKind::class,
        'runtime' => 'float',
        'recorded_at' => 'datetime',
    ];
}
