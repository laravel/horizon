<?php

namespace Laravel\Horizon\Models;

use Illuminate\Database\Eloquent\Model;

class HorizonState extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $guarded = [];
}
