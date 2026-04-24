<?php

namespace Laravel\Horizon\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Horizon\Enums\MetricKind;

class HorizonMetric extends Model
{
    protected $guarded = [];

    protected $casts = [
        'kind' => MetricKind::class,
        'throughput' => 'integer',
        'runtime' => 'float',
    ];
}
