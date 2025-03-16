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
    public function __construct(QueueFactory $queue, WaitTimeCalculator $waitTime,
                                MasterSupervisorRepository $masters, SupervisorRepository $supervisors)
    {
        $this->queue = $queue;
        $this->masters = $masters;
        $this->waitTime = $waitTime;
        $this->supervisors = $supervisors;
    }

    /**
     * Get the current workload of each queue.
     *
     * @return array<int, array{"name": string, "length": int, "wait": int, "processes": int, "split_queues": null|array<int, array{"name": string, "wait": int, "length": int}>}>
     */
    public function get()
    {
        $processes = $this->processes();

        return collect($this->waitTime->calculate())
            ->map(function ($waitTime, $queue) use ($processes) {
                [$connection, $queueName] = explode(':', $queue, 2);

                $totalProcesses = $processes[$queue] ?? 0;

                $length = ! Str::contains($queue, ',')
                    ? collect([$queueName => $this->queue->connection($connection)->readyNow($queueName)])
                    : collect(explode(',', $queueName))->mapWithKeys(function ($queueName) use ($connection) {
                        return [$queueName => $this->queue->connection($connection)->readyNow($queueName)];
                    });

                $splitQueues = Str::contains($queue, ',') ? $length->map(function ($length, $queueName) use ($connection, $totalProcesses, &$wait) {
                    return [
                        'name' => $queueName,
                        'length' => $length,
                        'wait' => $wait += $this->waitTime->calculateTimeToClear($connection, $queueName, $totalProcesses),
                    ];
                }) : null;

                return [
                    'name' => $queueName,
                    'length' => $length->sum(),
                    'wait' => $waitTime,
                    'processes' => $totalProcesses,
                    'split_queues' => $splitQueues,
                ];
            })->values()->toArray();
    }

    /**
     * Get the number of processes of each queue.
     *
     * @return array
     */
    private function processes()
    {
        return collect($this->supervisors->all())->pluck('processes')->reduce(function ($final, $queues) {
            foreach ($queues as $queue => $processes) {
                $final[$queue] = isset($final[$queue]) ? $final[$queue] + $processes : $processes;
            }

            return $final;
        }, []);
    }
}
