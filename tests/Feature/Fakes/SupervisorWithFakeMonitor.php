<?php

namespace Laravel\Horizon\Tests\Feature\Fakes;

use Laravel\Horizon\Supervisor;

class SupervisorWithFakeMonitor extends Supervisor
{
    public $monitoring = false;

    /**
     * {@inheritdoc}
     */
    public function monitor()
    {
        $this->monitoring = true;
    }
}
