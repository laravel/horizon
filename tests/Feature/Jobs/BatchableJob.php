<?php

namespace Laravel\Horizon\Tests\Feature\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BatchableJob implements ShouldQueue
{
    use Queueable, Batchable;

    public function handle()
    {
        //
    }
}
