<?php

namespace Laravel\Horizon;

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;

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
    public function __construct(
        QueueFactory $queue,
        SupervisorRepository $supervisors,
        MetricsRepository $metrics,
    ) {
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
     * Calculate the time to clear per queue for a given connection in seconds.
     *
     * @param  string  $connection
     * @param  string|null  $queue
     * @return array
     */
    public function calculateForConnection($connection, $queue = null)
    {
        $supervisors = collect($this->supervisors->all());

        return $this->calculateQueues(
            $this->queueNames(
                $supervisors->filter(fn ($supervisor) => $this->connectionFor($supervisor) === $connection),
                $queue
            ),
            $supervisors
        );
    }

    /**
     * Calculate the time to clear per queue in seconds.
     *
     * @param  string|null  $queue
     * @return array
     */
    public function calculate($queue = null)
    {
        $supervisors = collect($this->supervisors->all());

        return $this->calculateQueues(
            $this->queueNames($supervisors, $queue),
            $supervisors
        );
    }

    /**
     * Calculate the time to clear for the given queues.
     *
     * @param  \Illuminate\Support\Collection  $queues
     * @param  \Illuminate\Support\Collection  $supervisors
     * @return array
     */
    protected function calculateQueues($queues, $supervisors)
    {
        return $queues->mapWithKeys(function ($queue) use ($supervisors) {
            $totalProcesses = $this->totalProcessesFor($supervisors, $queue);

            [$connection, $queueName] = explode(':', $queue, 2);

            return [$queue => $this->calculateTimeToClear($connection, $queueName, $totalProcesses)];
        })
            ->sort()
            ->reverse()
            ->all();
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
        $queues = $supervisors->map(fn ($supervisor) => array_keys($this->processesFor($supervisor)))
            ->collapse()
            ->unique()
            ->values();

        return $queue ? $queues->intersect([$queue]) : $queues;
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
            $processes = $this->processesFor($supervisor);

            return $processes[$queue] ?? 0;
        });
    }

    /**
     * Get the queue connection for the given supervisor record.
     *
     * @param  object  $supervisor
     * @return string|null
     */
    protected function connectionFor($supervisor)
    {
        $options = $supervisor->options ?? null;

        return is_array($options) ? ($options['connection'] ?? null) : ($options->connection ?? null);
    }

    /**
     * Get the queue process counts for the given supervisor.
     *
     * @param  object  $supervisor
     * @return array
     */
    protected function processesFor($supervisor)
    {
        if (isset($supervisor->processes)) {
            return (array) $supervisor->processes;
        }

        if (isset($supervisor->processPools, $supervisor->options)) {
            return collect($supervisor->processPools)
                ->mapWithKeys(fn ($pool) => [$supervisor->options->connection.':'.$pool->queue() => count($pool->processes())])
                ->all();
        }

        return [];
    }

    /**
     * Calculate the time to clear for the given queue in seconds distributed over the given amount of processes.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  int  $totalProcesses
     * @return float
     */
    public function calculateTimeToClear($connection, $queue, $totalProcesses)
    {
        $timeToClear = ! Str::contains($queue ?? '', ',')
            ? $this->timeToClearFor($connection, $queue)
            : collect(explode(',', $queue))->sum(function ($queueName) use ($connection) {
                return $this->timeToClearFor($connection, $queueName);
            });

        return $totalProcesses === 0
            ? round($timeToClear / 1000)
            : round(($timeToClear / $totalProcesses) / 1000);
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
}
