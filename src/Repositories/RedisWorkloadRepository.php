<?php

namespace Laravel\Horizon\Repositories;

use Laravel\Horizon\WaitTimeCalculator;
use Laravel\Horizon\Contracts\WorkloadRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;

class RedisWorkloadRepository implements WorkloadRepository
{
    /**
     * The queue factory implementation.
     *
     * @var QueueFactory
     */
    public $queue;

    /**
     * The wait time calculator instance.
     *
     * @var WaitTimeCalculator
     */
    public $waitTime;

    /**
     * The master supervisor repository implementation.
     *
     * @var MasterSupervisorRepository
     */
    private $masters;

    /**
     * The supervisor repository implementation.
     *
     * @var SupervisorRepository
     */
    private $supervisors;

    /**
     * Create a new repository instance.
     *
     * @param  QueueFactory  $queue
     * @param  WaitTimeCalculator  $waitTime
     * @param  MasterSupervisorRepository  $masters
     * @param  SupervisorRepository  $supervisors
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
     * @return array
     */
    public function get()
    {
        $processes = $this->processes();

        return collect($this->waitTime->calculate())
            ->map(function ($waitTime, $queue) use ($processes) {
                [$connection, $queueName] = explode(':', $queue, 2);

                return [
                    'name' => $queueName,
                    'length' => $this->queue->connection($connection)->readyNow($queueName),
                    'wait' => $waitTime,
                    'processes' => $processes[$queue] ??  0,
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
