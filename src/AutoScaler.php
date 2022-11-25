<?php

namespace Laravel\Horizon;

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Support\Collection;
use Laravel\Horizon\Contracts\MetricsRepository;

class AutoScaler
{
    /**
     * The queue factory implementation.
     *
     * @var \Illuminate\Contracts\Queue\Factory
     */
    public $queue;

    /**
     * The metrics repository implementation.
     *
     * @var \Laravel\Horizon\Contracts\MetricsRepository
     */
    public $metrics;

    /**
     * Create a new auto-scaler instance.
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @param  \Laravel\Horizon\Contracts\MetricsRepository  $metrics
     * @return void
     */
    public function __construct(QueueFactory $queue, MetricsRepository $metrics)
    {
        $this->queue = $queue;
        $this->metrics = $metrics;
    }

    /**
     * Balance the workers on the given supervisor.
     *
     * @param  \Laravel\Horizon\Supervisor  $supervisor
     * @return void
     */
    public function scale(Supervisor $supervisor)
    {
        $pools = $this->poolsByQueue($supervisor);

        $workers = $this->numberOfWorkersPerQueue(
            $supervisor, $this->timeToClearPerQueue($supervisor, $pools)
        );

        $workers->each(function ($workers, $queue) use ($supervisor, $pools) {
            $this->scalePool($supervisor, $pools[$queue], $workers);
        });
    }

    /**
     * Get the process pools keyed by their queue name.
     *
     * @param  \Laravel\Horizon\Supervisor  $supervisor
     * @return \Illuminate\Support\Collection
     */
    protected function poolsByQueue(Supervisor $supervisor)
    {
        return $supervisor->processPools->mapWithKeys(function ($pool) {
            return [$pool->queue() => $pool];
        });
    }

    /**
     * Get the times in milliseconds needed to clear the queues.
     *
     * @param  \Laravel\Horizon\Supervisor  $supervisor
     * @param  \Illuminate\Support\Collection  $pools
     * @return \Illuminate\Support\Collection
     */
    protected function timeToClearPerQueue(Supervisor $supervisor, Collection $pools)
    {
        return $pools->mapWithKeys(function ($pool, $queue) use ($supervisor) {
            $size = $this->queue->connection($supervisor->options->connection)->readyNow($queue);

            return [$queue => [
                'size' => $size,
                'time' =>  ($size * $this->metrics->runtimeForQueue($queue)),
            ]];
        });
    }

    /**
     * Get the number of workers needed per queue for proper balance.
     *
     * @param  \Laravel\Horizon\Supervisor  $supervisor
     * @param  \Illuminate\Support\Collection  $queues
     * @return \Illuminate\Support\Collection
     */
    protected function numberOfWorkersPerQueue(Supervisor $supervisor, Collection $queues)
    {
        $timeToClearAll = $queues->sum('time');

        return $queues->mapWithKeys(function ($timeToClear, $queue) use ($supervisor, $timeToClearAll) {
            if ($timeToClearAll > 0 &&
                $supervisor->options->autoScaling()) {
                return [$queue => (($timeToClear['time'] / $timeToClearAll) * $supervisor->options->maxProcesses)];
            } elseif ($timeToClearAll == 0 &&
                      $supervisor->options->autoScaling()) {
                return [
                    $queue => $timeToClear['size']
                                ? $supervisor->options->maxProcesses
                                : $supervisor->options->minProcesses,
                ];
            }

            return [$queue => $supervisor->options->maxProcesses / count($supervisor->processPools)];
        })->sort();
    }

    /**
     * Scale the given pool to the recommended number of workers.
     *
     * @param  \Laravel\Horizon\Supervisor  $supervisor
     * @param  \Laravel\Horizon\ProcessPool  $pool
     * @param  float  $workers
     * @return void
     */
    protected function scalePool(Supervisor $supervisor, $pool, $workers)
    {
        $supervisor->pruneTerminatingProcesses();

        $totalProcessCount = $pool->totalProcessCount();

        $desiredProcessCount = ceil($workers);

        if ($desiredProcessCount > $totalProcessCount) {
            $maxUpShift = min(
                max(0, $supervisor->options->maxProcesses - $supervisor->totalProcessCount()),
                $supervisor->options->balanceMaxShift
            );

            $pool->scale(
                min(
                    $totalProcessCount + $maxUpShift,
                    $supervisor->options->maxProcesses - (($supervisor->processPools->count() - 1) * $supervisor->options->minProcesses),
                    $desiredProcessCount
                )
            );
        } elseif ($desiredProcessCount < $totalProcessCount) {
            $maxDownShift = min(
                $supervisor->totalProcessCount() - $supervisor->options->minProcesses,
                $supervisor->options->balanceMaxShift
            );

            $pool->scale(
                max(
                    $totalProcessCount - $maxDownShift,
                    $supervisor->options->minProcesses,
                    $desiredProcessCount
                )
            );
        }
    }
}
