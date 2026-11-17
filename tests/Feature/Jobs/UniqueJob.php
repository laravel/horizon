<?php

namespace Laravel\Horizon\Tests\Feature\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UniqueJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, Queueable;

    public function __construct(public string $reference)
    {
        //
    }

    public function uniqueId()
    {
        return $this->reference;
    }

    public function uniqueFor()
    {
        return 3600;
    }

    public function handle()
    {
        //
    }
}
