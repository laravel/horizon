<?php

namespace Laravel\Horizon;

use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Illuminate\Contracts\Queue\Factory as QueueFactory;

class WaitTimeCalculator
{
    /**
     * The queue factory implementation.
     *
     * @var QueueFactory
     */
    public $queue;

    /**
     * The supervisor repository implementation.
     *
     * @var SupervisorRepository
     */
    public $supervisors;

    /**
     * The metrics repository implementation.
     *
     * @var MetricsRepository
     */
    public $metrics;

    /**
     * Create a new calculator instance.
     *
     * @param  QueueFactory  $queue
     * @param  SupervisorRepository  $supervisors
     * @param  MetricsRepository  $metrics
     * @return void
     */
    public function __construct(QueueFactory $queue,
                                SupervisorRepository $supervisors,
                                MetricsRepository $metrics)
    {
        $this->queue = $queue;
        $this->metrics = $metrics;
        $this->supervisors = $supervisors;
    }

    /**
     * Calculate the time to clear a given queue in seconds.
     *
     * @param  string  $queue
     * @return float
     */
    public function calculateFor($queue)
    {
        return array_values($this->calculate($queue))[0] ?? 0;
    }

    /**
     * Calculate the time to clear per queue in seconds.
     *
     * @param  string|null  $queue
     * @return array
     */
    public function calculate($queue = null)
    {
        $queues = $this->queueNames(
            $supervisors = collect($this->supervisors->all()), $queue
        );

        return $queues->mapWithKeys(function ($queue) use ($supervisors) {
            $totalProcesses = $this->totalProcessesFor($supervisors, $queue);

            [$connection, $name] = explode(':', $queue, 2);

            return $totalProcesses === 0
                    ? [$queue => round($this->timeToClearFor($connection, $name) / 1000)]
                    : [$queue => round(($this->timeToClearFor($connection, $name) / $totalProcesses) / 1000)];
        })->sort()->reverse()->all();
    }

    /**
     * Get all of the queue names.
     *
     * @param  \Illuminate\Support\Collection  $supervisors
     * @param  string|null  $queue
     * @return \Illuminate\Support\Collection
     */
    protected function queueNames($supervisors, $queue = null)
    {
        $queues = $supervisors->map(function ($supervisor) {
            return array_keys($supervisor->processes);
        })->collapse()->unique()->values();

        return $queue ? $queues->intersect([$queue]) : $queues;
    }

    /**
     * Get the total time to clear (in milliseconds) for a given queue.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @return float
     */
    protected function timeToClearFor($connection, $queue)
    {
        $size = $this->queue->connection($connection)->readyNow($queue);

        return $size * $this->metrics->runtimeForQueue($queue);
    }

    /**
     * Get the total process count for a given queue.
     *
     * @param  \Illuminate\Support\Collection  $allSupervisors
     * @param  string  $queue
     * @return int
     */
    protected function totalProcessesFor($allSupervisors, $queue)
    {
        return $allSupervisors->sum(function ($supervisor) use ($queue) {
            return $supervisor->processes[$queue] ?? 0;
        });
    }
}
