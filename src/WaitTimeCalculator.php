<?php

namespace Laravel\Horizon;

use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Illuminate\Contracts\Queue\Factory as QueueFactory;

class WaitTimeCalculator
{
    /**
     * The queue factory implementation.
     *
     * @var \Illuminate\Contracts\Queue\Factory
     */
    public $queue;

    /**
     * The supervisor repository implementation.
     *
     * @var \Laravel\Horizon\Contracts\SupervisorRepository
     */
    public $supervisors;

    /**
     * The metrics repository implementation.
     *
     * @var \Laravel\Horizon\Contracts\MetricsRepository
     */
    public $metrics;

    /**
     * Create a new calculator instance.
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @param  \Laravel\Horizon\Contracts\SupervisorRepository  $supervisors
     * @param  \Laravel\Horizon\Contracts\MetricsRepository  $metrics
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

            [$connection, $queueName] = explode(':', $queue, 2);

            $timeToClear = ! Str::contains($queueName, ',')
                ? $this->timeToClearFor($connection, $queueName)
                : collect(explode(',', $queueName))->sum(function ($queueName) use ($connection) {
                    return $this->timeToClearFor($connection, $queueName);
                });

            return $totalProcesses === 0
                    ? [$queue => round($timeToClear / 1000)]
                    : [$queue => round(($timeToClear / $totalProcesses) / 1000)];
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
