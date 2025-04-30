<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Events\SupervisorLooped;
use Laravel\Horizon\Events\SupervisorOutOfMemory;

class MonitorSupervisorMemory
{
    /**
     * Handle the event.
     *
     * @param  \Laravel\Horizon\Events\SupervisorLooped  $event
     * @return void
     */
    public function handle(SupervisorLooped $event)
    {
        $supervisor = $event->supervisor;

        if (($memoryUsage = $supervisor->memoryUsage()) > $supervisor->options->memory) {
            event((new SupervisorOutOfMemory($supervisor))->setMemoryUsage($memoryUsage));

            $supervisor->terminate(12);
        }
    }
}
