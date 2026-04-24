<?php

namespace Laravel\Horizon\Models;

use Illuminate\Database\Eloquent\Model;

class HorizonMonitoredTag extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'tag';

    protected $keyType = 'string';

    protected $guarded = [];
}
