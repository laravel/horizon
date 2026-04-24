<?php

namespace Laravel\Horizon\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Horizon\Enums\JobReferenceType;

class HorizonJobReference extends Model
{
    protected $guarded = [];

    protected $casts = [
        'type' => JobReferenceType::class,
        'score' => 'float',
    ];
}
