<?php

namespace Laravel\Horizon\Tests\Feature\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UniqueJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, Queueable;

    public $uniqueId;

    public function __construct($uniqueId)
    {
        $this->uniqueId = $uniqueId;
    }

    public function handle()
    {
        //
    }
}
