<?php

namespace Laravel\Horizon\Tests\Feature\Jobs;

use Exception;

class FailingJob
{
    public function handle()
    {
        throw new Exception('Job Failed');
    }

    public function tags()
    {
        return ['first'];
    }
}
