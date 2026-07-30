<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Queue\QueueManager;
use Illuminate\Support\Collection;
use Laravel\Horizon\Contracts\WorkloadRepository;
use Laravel\Horizon\Horizon;
use Traversable;

class WorkloadController extends Controller
{
    /**
     * Get the current queue workload for the application.
     *
     * @param  \Laravel\Horizon\Contracts\WorkloadRepository  $workload
     * @param  \Illuminate\Queue\QueueManager  $queues
     * @return array
     */
    public function index(WorkloadRepository $workload, QueueManager $queues)
    {
        $workload = collect($workload->get())
            ->sortBy('name')
            ->values()
            ->toArray();

        $supportsQueuePausing = Horizon::supportsQueuePausing();
        $supportsTimedQueuePausing = Horizon::supportsTimedQueuePausing();

        $paused = $supportsQueuePausing
            ? $this->pausedQueues($queues, $workload)
            : [];

        return array_map(
            function ($queue) use ($paused, $supportsQueuePausing, $supportsTimedQueuePausing) {
                return $this->decorateQueue(
                    $queue,
                    $paused,
                    $supportsQueuePausing,
                    $supportsTimedQueuePausing
                );
            },
            $workload
        );
    }

    /**
     * Get the paused queues grouped by connection and name.
     *
     * @param  \Illuminate\Queue\QueueManager  $queues
     * @param  array  $workload
     * @return array
     */
    protected function pausedQueues(QueueManager $queues, array $workload)
    {
        $paused = [];

        foreach ($workload as $queue) {
            $connection = $this->connectionName($queue);

            if ($connection === null) {
                continue;
            }

            foreach ($this->queueNames($queue) as $name) {
                if ($queues->isPaused($connection, $name)) {
                    $paused[$connection.':'.$name] = true;
                }
            }
        }

        return $paused;
    }

    /**
     * Add queue pause information to a workload entry.
     *
     * @param  array  $queue
     * @param  array  $paused
     * @param  bool  $supportsQueuePausing
     * @param  bool  $supportsTimedQueuePausing
     * @return array
     */
    protected function decorateQueue(array $queue, array $paused, $supportsQueuePausing, $supportsTimedQueuePausing)
    {
        $queue['split_queues'] = $this->normalizeSplitQueues($queue['split_queues'] ?? null);

        $connection = $this->connectionName($queue);
        $pausingSupported = $supportsQueuePausing && $connection !== null;
        $timedPausingSupported = $supportsTimedQueuePausing && $connection !== null;
        $hasSplits = is_array($queue['split_queues']);

        $queue['queue_pausing_supported'] = $pausingSupported;
        $queue['timed_queue_pausing_supported'] = $timedPausingSupported;
        $queue['paused'] = $pausingSupported && ! $hasSplits
            ? isset($paused[$connection.':'.$queue['name']])
            : null;

        if ($hasSplits) {
            $queue['split_queues'] = array_map(function ($splitQueue) use ($connection, $paused, $pausingSupported, $timedPausingSupported) {
                if (! is_array($splitQueue)) {
                    $splitQueue = ['name' => $splitQueue];
                }

                if ($connection !== null) {
                    $splitQueue['connection'] = $connection;
                }

                $splitQueue['queue_pausing_supported'] = $pausingSupported;
                $splitQueue['timed_queue_pausing_supported'] = $timedPausingSupported;
                $splitQueue['paused'] = $pausingSupported
                    ? isset($paused[$connection.':'.($splitQueue['name'] ?? '')])
                    : null;

                return $splitQueue;
            }, $queue['split_queues']);
        }

        return $queue;
    }

    /**
     * Resolve a usable queue connection name from a workload entry.
     *
     * @param  array  $queue
     * @return string|null
     */
    protected function connectionName(array $queue)
    {
        $connection = $queue['connection'] ?? null;

        return is_string($connection) && $connection !== ''
            ? $connection
            : null;
    }

    /**
     * Normalize split_queues to null or a list of entries.
     *
     * @param  mixed  $splitQueues
     * @return array|null
     */
    protected function normalizeSplitQueues($splitQueues)
    {
        if ($splitQueues === null) {
            return null;
        }

        if ($splitQueues instanceof Collection) {
            $splitQueues = $splitQueues->values()->all();
        } elseif ($splitQueues instanceof Traversable) {
            $splitQueues = iterator_to_array($splitQueues, false);
        } elseif (! is_array($splitQueues)) {
            return null;
        }

        return array_values($splitQueues);
    }

    /**
     * Get the queue names represented by a workload entry.
     *
     * @param  array  $queue
     * @return array
     */
    protected function queueNames(array $queue)
    {
        $splitQueues = $this->normalizeSplitQueues($queue['split_queues'] ?? null);

        if (is_array($splitQueues) && count($splitQueues) > 0) {
            $names = [];

            foreach ($splitQueues as $splitQueue) {
                if (is_array($splitQueue) && isset($splitQueue['name']) && is_string($splitQueue['name'])) {
                    $names[] = $splitQueue['name'];
                }
            }

            return array_values(array_unique($names));
        }

        $name = $queue['name'] ?? null;

        return is_string($name) && $name !== '' ? [$name] : [];
    }
}
