<?php

namespace Laravel\Horizon\Http\Controllers;

use Laravel\Horizon\Contracts\WorkloadRepository;

class WorkloadController extends Controller
{
    /**
     * Get the current queue workload for the application.
     *
     * @param  \Laravel\Horizon\Contracts\WorkloadRepository  $workload
     * @return array
     */
    public function index(WorkloadRepository $workload)
    {
        return collect($workload->get())
            ->sortBy('name')
            ->values()
            ->map(function ($queue) {
                // Access queue data as an array
                $connection = $queue['connection'] ?? 'redis';
                $queueName = $queue['queue_name'] ?? $queue['name'];

                // Add pause status
                $queue['is_paused'] = \Illuminate\Support\Facades\Queue::isPaused($connection, $queueName);

                return $queue;
            })
            ->toArray();
    }
}
