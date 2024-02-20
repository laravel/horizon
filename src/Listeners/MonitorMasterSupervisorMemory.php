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

        $memoryLimit = config('horizon.memory_limit', 64);

        if ($master->memoryUsage() > $memoryLimit) {
            event(new MasterSupervisorOutOfMemory($master));

            $master->output('error', 'Memory limit exceeded: Using '.ceil($master->memoryUsage()).'/'.$memoryLimit.'MB. Consider increasing horizon.memory_limit.');

            $master->terminate(12);
        }
    }
}
