<?php

namespace Laravel\Horizon\Tests\Feature\Fixtures;

use Laravel\Horizon\Tests\Feature\Fakes\SupervisorWithFakeMonitor;

class FakeSupervisorFactory
{
    public $supervisor;

    public function make($options)
    {
        return $this->supervisor = new SupervisorWithFakeMonitor($options);
    }
}
