<?php

namespace Laravel\Horizon\Tests\Feature\Jobs;

use Illuminate\Queue\InteractsWithQueue;

class ConditionallyFailingJob
{
    use InteractsWithQueue;

    public function handle()
    {
        if (isset($_SERVER['horizon.fail'])) {
            return $this->fail();
        }
    }

    public function tags()
    {
        return ['first'];
    }
}
