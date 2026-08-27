<?php

namespace Laravel\Horizon\Tests\Feature\Fakes;

class SupervisorWithFakeScaling extends SupervisorWithFakeMonitor
{
    public $scaledTo;

    /**
     * {@inheritdoc}
     */
    public function scale($processes)
    {
        $this->scaledTo = $processes;
    }
}
