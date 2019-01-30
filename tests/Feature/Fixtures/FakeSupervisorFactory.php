<?php

namespace Laravel\Horizon\Tests\Feature\Fixtures;

use Laravel\Horizon\SupervisorFactory;
use Laravel\Horizon\Tests\Feature\Fakes\SupervisorWithFakeMonitor;

class FakeSupervisorFactory extends SupervisorFactory
{
    public $supervisor;

    public function make($options)
    {
        return $this->supervisor = new SupervisorWithFakeMonitor($options);
    }
}
