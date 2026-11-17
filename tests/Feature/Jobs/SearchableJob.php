<?php

namespace Laravel\Horizon\Tests\Feature\Jobs;

use Exception;

class SearchableJob
{
    public function __construct(public string $reference, public bool $fails = false)
    {
        //
    }

    public function handle()
    {
        if ($this->fails) {
            throw new Exception('Payment gateway unavailable');
        }
    }
}
