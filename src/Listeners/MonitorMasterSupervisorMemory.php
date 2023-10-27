<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Events\MasterSupervisorLooped;
use Laravel\Horizon\Events\MasterSupervisorOutOfMemory;

class MonitorMasterSupervisorMemory
{
    /**
     * Handle the event.
     *
     * @param  \Laravel\Horizon\Events\MasterSupervisorLooped  $event
     * @return void
     */
    public function handle(MasterSupervisorLooped $event)
    {
        $master = $event->master;

        if ($master->memoryUsage() > config('horizon.memory_limit', 64)) {
            event(new MasterSupervisorOutOfMemory($master));

            $master->terminate(12);
        }
    }
}
