<?php

namespace Laravel\Horizon\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionResolverInterface;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Lock;
use Laravel\Horizon\WaitTimeCalculator;

class DatabaseMetricsRepository implements MetricsRepository
{
    /**
     * The database connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    public $resolver;

    /**
     * Create a new repository instance.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @return void
     */
    public function __construct(ConnectionResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Get all of the class names that have metrics measurements.
     *
     * @return array
     */
    public function measuredJobs()
    {
        $keys = $this->metricsTable()->where('type', 'job')->pluck('key')->all();

        return collect($keys)->map(function ($key) {
            return preg_match('/job:(.*)$/', $key, $matches) ? $matches[1] : $key;
        })->sort()->values()->all();
    }

    /**
     * Get all of the queues that have metrics measurements.
     *
     * @return array
     */
    public function measuredQueues()
    {
        $keys = $this->metricsTable()->where('type', 'queue')->pluck('key')->all();

        return collect($keys)->map(function ($key) {
            return preg_match('/queue:(.*)$/', $key, $matches) ? $matches[1] : $key;
        })->sort()->values()->all();
    }

    /**
     * Get the jobs processed per minute since the last snapshot.
     *
     * @return float
     */
    public function jobsProcessedPerMinute()
    {
        return round($this->throughput() / $this->minutesSinceLastSnapshot());
    }

    /**
     * Get the application's total throughput since the last snapshot.
     *
     * @return int
     */
    public function throughput()
    {
        return (int) $this->metricsTable()->where('type', 'queue')->sum('throughput');
    }

    /**
     * Get the throughput for a given job.
     *
     * @param  string  $job
     * @return int
     */
    public function throughputForJob($job)
    {
        return $this->throughputFor('job:'.$job);
    }

    /**
     * Get the throughput for a given queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function throughputForQueue($queue)
    {
        return $this->throughputFor('queue:'.$queue);
    }

    /**
     * Get the throughput for a given key.
     *
     * @param  string  $key
     * @return int
     */
    protected function throughputFor($key)
    {
        return (int) $this->metricsTable()->where('key', $key)->value('throughput');
    }

    /**
     * Get the average runtime for a given job in milliseconds.
     *
     * @param  string  $job
     * @return float
     */
    public function runtimeForJob($job)
    {
        return $this->runtimeFor('job:'.$job);
    }

    /**
     * Get the average runtime for a given queue in milliseconds.
     *
     * @param  string  $queue
     * @return float
     */
    public function runtimeForQueue($queue)
    {
        return $this->runtimeFor('queue:'.$queue);
    }

    /**
     * Get the average runtime for a given key in milliseconds.
     *
     * @param  string  $key
     * @return float
     */
    protected function runtimeFor($key)
    {
        return (float) $this->metricsTable()->where('key', $key)->value('runtime');
    }

    /**
     * Get the queue that has the longest runtime.
     *
     * @return int
     */
    public function queueWithMaximumRuntime()
    {
        return collect($this->measuredQueues())->sortBy(function ($queue) {
            $snapshot = $this->latestSnapshotFor('queue:'.$queue);

            return $snapshot ? $snapshot->runtime : 0;
        })->last();
    }

    /**
     * Get the queue that has the most throughput.
     *
     * @return int
     */
    public function queueWithMaximumThroughput()
    {
        return collect($this->measuredQueues())->sortBy(function ($queue) {
            $snapshot = $this->latestSnapshotFor('queue:'.$queue);

            return $snapshot ? $snapshot->throughput : 0;
        })->last();
    }

    /**
     * Get the latest snapshot stored for the given key.
     *
     * @param  string  $key
     * @return \stdClass|null
     */
    protected function latestSnapshotFor($key)
    {
        $record = $this->snapshotsTable()
            ->where('key', $key)
            ->orderBy('taken_at', 'desc')
            ->first();

        return $record ? (object) json_decode($record->snapshot, true) : null;
    }

    /**
     * Increment the metrics information for a job.
     *
     * @param  string  $job
     * @param  float  $runtime
     * @return void
     */
    public function incrementJob($job, $runtime)
    {
        $this->incrementMetric('job:'.$job, 'job', $runtime);
    }

    /**
     * Increment the metrics information for a queue.
     *
     * @param  string  $queue
     * @param  float  $runtime
     * @return void
     */
    public function incrementQueue($queue, $runtime)
    {
        $this->incrementMetric('queue:'.$queue, 'queue', $runtime);
    }

    /**
     * Increment the metric counters for a key, recomputing the running average runtime.
     *
     * @param  string  $key
     * @param  string  $type
     * @param  float  $runtime
     * @return void
     */
    protected function incrementMetric($key, $type, $runtime)
    {
        $this->connection()->transaction(function () use ($key, $type, $runtime) {
            $current = $this->metricsTable()->where('key', $key)->lockForUpdate()->first();

            if ($current) {
                $newThroughput = $current->throughput + 1;
                $newRuntime = (($current->runtime * $current->throughput) + $runtime) / $newThroughput;

                $this->metricsTable()->where('key', $key)->update([
                    'throughput' => $newThroughput,
                    'runtime' => $newRuntime,
                ]);
            } else {
                $this->metricsTable()->insert([
                    'key' => $key,
                    'type' => $type,
                    'throughput' => 1,
                    'runtime' => $runtime,
                ]);
            }
        });
    }

    /**
     * Get all of the snapshots for the given job.
     *
     * @param  string  $job
     * @return array
     */
    public function snapshotsForJob($job)
    {
        return $this->snapshotsFor('job:'.$job);
    }

    /**
     * Get all of the snapshots for the given queue.
     *
     * @param  string  $queue
     * @return array
     */
    public function snapshotsForQueue($queue)
    {
        return $this->snapshotsFor('queue:'.$queue);
    }

    /**
     * Get all of the snapshots for the given key.
     *
     * @param  string  $key
     * @return array
     */
    protected function snapshotsFor($key)
    {
        return $this->snapshotsTable()
            ->where('key', $key)
            ->orderBy('taken_at')
            ->pluck('snapshot')
            ->map(function ($snapshot) {
                return (object) json_decode($snapshot, true);
            })->values()->all();
    }

    /**
     * Store a snapshot of the metrics information.
     *
     * @return void
     */
    public function snapshot()
    {
        collect($this->measuredJobs())->each(function ($job) {
            $this->storeSnapshotForJob($job);
        });

        collect($this->measuredQueues())->each(function ($queue) {
            $this->storeSnapshotForQueue($queue);
        });

        $this->storeSnapshotTimestamp();
    }

    /**
     * Store a snapshot for the given job.
     *
     * @param  string  $job
     * @return void
     */
    protected function storeSnapshotForJob($job)
    {
        $data = $this->baseSnapshotData($key = 'job:'.$job, 'job');

        $this->snapshotsTable()->insert([
            'key' => $key,
            'type' => 'job',
            'taken_at' => $time = CarbonImmutable::now()->getTimestamp(),
            'snapshot' => json_encode([
                'throughput' => $data['throughput'],
                'runtime' => $data['runtime'],
                'time' => $time,
            ]),
        ]);

        $this->trimSnapshots($key, config('horizon.metrics.trim_snapshots.job', 24));
    }

    /**
     * Store a snapshot for the given queue.
     *
     * @param  string  $queue
     * @return void
     */
    protected function storeSnapshotForQueue($queue)
    {
        $data = $this->baseSnapshotData($key = 'queue:'.$queue, 'queue');

        $this->snapshotsTable()->insert([
            'key' => $key,
            'type' => 'queue',
            'taken_at' => $time = CarbonImmutable::now()->getTimestamp(),
            'snapshot' => json_encode([
                'throughput' => $data['throughput'],
                'runtime' => $data['runtime'],
                'wait' => app(WaitTimeCalculator::class)->calculateFor($queue),
                'time' => $time,
            ]),
        ]);

        $this->trimSnapshots($key, config('horizon.metrics.trim_snapshots.queue', 24));
    }

    /**
     * Trim the snapshots stored for a given key.
     *
     * @param  string  $key
     * @param  int  $keep
     * @return void
     */
    protected function trimSnapshots($key, $keep)
    {
        $keep = max(1, (int) $keep);

        $ids = $this->snapshotsTable()
            ->where('key', $key)
            ->orderBy('taken_at', 'desc')
            ->offset($keep)
            ->limit(1000)
            ->pluck('id')
            ->all();

        if (! empty($ids)) {
            $this->snapshotsTable()->whereIn('id', $ids)->delete();
        }
    }

    /**
     * Get the base snapshot data for a given key, resetting its counters.
     *
     * @param  string  $key
     * @param  string  $type
     * @return array
     */
    protected function baseSnapshotData($key, $type)
    {
        return $this->connection()->transaction(function () use ($key) {
            $record = $this->metricsTable()->where('key', $key)->lockForUpdate()->first();

            $data = [
                'throughput' => $record ? (int) $record->throughput : 0,
                'runtime' => $record ? (float) $record->runtime : 0.0,
            ];

            if ($record) {
                $this->metricsTable()->where('key', $key)->delete();
            }

            return $data;
        });
    }

    /**
     * Get the number of minutes passed since the last snapshot.
     *
     * @return float
     */
    protected function minutesSinceLastSnapshot()
    {
        $lastSnapshotAt = (int) ($this->metaTable()->where('key', 'last_snapshot_at')->value('value')
                                    ?: $this->storeSnapshotTimestamp());

        return max(
            (CarbonImmutable::now()->getTimestamp() - $lastSnapshotAt) / 60, 1
        );
    }

    /**
     * Store the current timestamp as the "last snapshot timestamp".
     *
     * @return int
     */
    protected function storeSnapshotTimestamp()
    {
        return tap(CarbonImmutable::now()->getTimestamp(), function ($timestamp) {
            $this->metaTable()->updateOrInsert(
                ['key' => 'last_snapshot_at'], ['value' => (string) $timestamp]
            );
        });
    }

    /**
     * Attempt to acquire a lock to monitor the queue wait times.
     *
     * @return bool
     */
    public function acquireWaitTimeMonitorLock()
    {
        return app(Lock::class)->get('monitor:time-to-clear');
    }

    /**
     * Clear the metrics for a key.
     *
     * @param  string  $key
     * @return void
     */
    public function forget($key)
    {
        $this->metricsTable()->where('key', $key)->delete();
        $this->snapshotsTable()->where('key', $key)->delete();
    }

    /**
     * Delete all stored metrics information.
     *
     * @return void
     */
    public function clear()
    {
        $this->metricsTable()->delete();
        $this->snapshotsTable()->delete();
        $this->metaTable()->delete();
    }

    /**
     * Get a query builder for the horizon metrics table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function metricsTable()
    {
        return $this->connection()->table('horizon_metrics');
    }

    /**
     * Get a query builder for the horizon metric snapshots table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function snapshotsTable()
    {
        return $this->connection()->table('horizon_metric_snapshots');
    }

    /**
     * Get a query builder for the horizon metric meta table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function metaTable()
    {
        return $this->connection()->table('horizon_metric_meta');
    }

    /**
     * Get the database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    public function connection()
    {
        return $this->resolver->connection(config('horizon.database.connection'));
    }
}
