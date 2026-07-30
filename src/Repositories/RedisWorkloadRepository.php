<?php

namespace Laravel\Horizon\Repositories;

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;
use Laravel\Horizon\WaitTimeCalculator;

class RedisWorkloadRepository implements WorkloadRepository
{
    /**
     * The queue factory implementation.
     *
     * @var \Illuminate\Contracts\Queue\Factory
     */
    public $queue;

    /**
     * The wait time calculator instance.
     *
     * @var \Laravel\Horizon\WaitTimeCalculator
     */
    public $waitTime;

    /**
     * The master supervisor repository implementation.
     *
     * @var \Laravel\Horizon\Contracts\MasterSupervisorRepository
     */
    private $masters;

    /**
     * The supervisor repository implementation.
     *
     * @var \Laravel\Horizon\Contracts\SupervisorRepository
     */
    private $supervisors;

    /**
     * Create a new repository instance.
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @param  \Laravel\Horizon\WaitTimeCalculator  $waitTime
     * @param  \Laravel\Horizon\Contracts\MasterSupervisorRepository  $masters
     * @param  \Laravel\Horizon\Contracts\SupervisorRepository  $supervisors
     * @return void
     */
    public function __construct(
        QueueFactory $queue,
        WaitTimeCalculator $waitTime,
        MasterSupervisorRepository $masters,
        SupervisorRepository $supervisors,
    ) {
        $this->queue = $queue;
        $this->masters = $masters;
        $this->waitTime = $waitTime;
        $this->supervisors = $supervisors;
    }

    /**
     * Determine if Horizon is actively processing any jobs.
     *
     * @return bool
     */
    public function processing()
    {
        return collect(array_keys($this->processes()))->contains(function ($queue) {
            [$connection, $queueNames] = explode(':', $queue, 2);

            $queueConnection = $this->queue->connection($connection);

            return collect(explode(',', $queueNames))->contains(
                fn ($queueName) => $queueConnection->pendingState($queueName)['reserved'] > 0
            );
        });
    }

    /**
     * Get the current workload of each queue.
     *
     * @return array<int, array{"connection": string, "name": string, "length": int, "reserved": int, "delayed": int, "wait": int, "processes": int, "split_queues": null|array<int, array{"connection": string, "name": string, "wait": int, "length": int}>}>
     */
    public function get()
    {
        $processes = $this->processes();

        return collect($processes)
            ->map(function ($totalProcesses, $queue) {
                [$connection, $queueName] = explode(':', $queue, 2);

                $queueConnection = $this->queue->connection($connection);

                $pending = collect(explode(',', $queueName))
                    ->mapWithKeys(fn ($queueName) => [$queueName => $queueConnection->pendingState($queueName)]);

                $ready = $pending->mapWithKeys(fn ($state, $queueName) => [$queueName => $state['ready']]);

                $waitTime = $this->waitTime->calculateTimeToClear(
                    $connection,
                    $queueName,
                    $totalProcesses,
                    $ready->all(),
                );

                $wait = 0;

                $splitQueues = Str::contains($queueName, ',')
                    ? $ready->map(function ($length, $queueName) use ($connection, $totalProcesses, &$wait) {
                        return [
                            'connection' => $connection,
                            'name' => $queueName,
                            'length' => $length,
                            'wait' => $wait += $this->waitTime->calculateTimeToClear(
                                $connection,
                                $queueName,
                                $totalProcesses,
                                [$queueName => $length],
                            ),
                        ];
                    })->values()->all()
                    : null;

                return [
                    'connection' => $connection,
                    'name' => $queueName,
                    'length' => $pending->sum('ready'),
                    'reserved' => $pending->sum('reserved'),
                    'delayed' => $pending->sum('delayed'),
                    'wait' => $waitTime,
                    'processes' => $totalProcesses,
                    'split_queues' => $splitQueues,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get the number of processes of each queue.
     *
     * @return array
     */
    private function processes()
    {
        return collect($this->supervisors->all())
            ->pluck('processes')
            ->reduce(function ($final, $queues) {
                foreach ($queues as $queue => $processes) {
                    $final[$queue] = isset($final[$queue]) ? $final[$queue] + $processes : $processes;
                }

                return $final;
            }, []);
    }
}
