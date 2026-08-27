<?php

namespace Laravel\Horizon\Tests\Feature\Fixtures;

use Laravel\Horizon\SupervisorFactory;
use Laravel\Horizon\SupervisorOptions;
use Laravel\Horizon\Tests\Feature\Fakes\SupervisorWithFakeMonitor;

class FakeSupervisorFactory extends SupervisorFactory
{
    public $supervisor;

    public $supervisorClass;

    public function __construct($supervisorClass = SupervisorWithFakeMonitor::class)
    {
        $this->supervisorClass = $supervisorClass;
    }

    public function make(SupervisorOptions $options)
    {
        return $this->supervisor = new $this->supervisorClass($options);
    }
}
