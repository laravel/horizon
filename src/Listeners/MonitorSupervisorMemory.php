<?php

namespace Laravel\Horizon\Listeners;

use Illuminate\Support\Facades\Log;
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

            Log::warning('Horizon supervisor memory limit exceeded, terminating.', [
                'supervisor' => $supervisor->name,
                'memory_used_mb' => round($memoryUsage, 1),
                'memory_limit_mb' => $supervisor->options->memory,
            ]);

            $supervisor->terminate(12);
        }
    }
}
