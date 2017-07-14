<?php

namespace Laravel\Horizon\Repositories;

use Cake\Chronos\Chronos;
use Laravel\Horizon\Lock;
use Laravel\Horizon\LuaScripts;
use Laravel\Horizon\WaitTimeCalculator;
use Laravel\Horizon\Contracts\MetricsRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;

class RedisMetricsRepository implements MetricsRepository
{
    /**
     * The Redis connection instance.
     *
     * @var RedisFactory
     */
    public $redis;

    /**
     * Create a new repository instance.
     *
     * @param  RedisFactory
     * @return void
     */
    public function __construct(RedisFactory $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Get all of the class names that have metrics measurements.
     *
     * @return array
     */
    public function measuredJobs()
    {
        $classes = (array) $this->connection()->keys('job:*');

        return collect($classes)->map(function ($class) {
            return substr($class, 4);
        })->all();
    }

    /**
     * Get all of the queues that have metrics measurements.
     *
     * @return array
     */
    public function measuredQueues()
    {
        $queues = (array) $this->connection()->keys('queue:*');

        return collect($queues)->map(function ($class) {
            return substr($class, 6);
        })->all();
    }

    /**
     * Get the jobs processed per minute since the last snapshot.
     *
     * @return int
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
        return collect($this->measuredQueues())->reduce(function ($carry, $queue) {
            return $carry + $this->connection()->hget('queue:'.$queue, 'throughput');
        }, 0);
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
        return (int) $this->connection()->hget($key, 'throughput');
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
        return (float) $this->connection()->hget($key, 'runtime');
    }

    /**
     * Get the queue that has the longest runtime.
     *
     * @return int
     */
    public function queueWithMaximumRuntime()
    {
        return collect($this->measuredQueues())->sortBy(function ($queue) {
            if ($snapshots = $this->connection()->zrange('snapshot:queue:'.$queue, - 1, 1)) {
                return json_decode($snapshots[0])->runtime;
            }
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
            if ($snapshots = $this->connection()->zrange('snapshot:queue:'.$queue, - 1, 1)) {
                return json_decode($snapshots[0])->throughput;
            }
        })->last();
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
        return $this->increment('job:'.$job, $runtime);
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
        return $this->increment('queue:'.$queue, $runtime);
    }

    /**
     * Increment the metrics information for a key.
     *
     * @param  string  $key
     * @param  float  $runtime
     * @return void
     */
    protected function increment($key, $runtime)
    {
        $this->connection()->pipeline(function ($pipe) use ($key, $runtime) {
            $pipe->hsetnx($key, 'throughput', 0);

            $pipe->eval(LuaScripts::updateJobMetrics(), 1, $key, $runtime);
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
        $snapshots = array_flip($this->connection()->zrange('snapshot:'.$key, 0, -1, 'withscores'));

        return collect($snapshots)->map(function ($snapshot, $time) {
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
        $data = $this->baseSnapshotData($key = 'job:'.$job);

        $this->connection()->zadd(
            'snapshot:'.$key, $time = Chronos::now()->getTimestamp(), json_encode([
                'throughput' => $data['throughput'],
                'runtime' => $data['runtime'],
                'time' => $time,
            ])
        );

        $this->connection()->zremrangebyrank(
            'snapshot:'.$key, 0, -25
        );
    }

    /**
     * Store a snapshot for the given queue.
     *
     * @param  string  $queue
     * @return void
     */
    protected function storeSnapshotForQueue($queue)
    {
        $data = $this->baseSnapshotData($key = 'queue:'.$queue);

        $this->connection()->zadd(
            'snapshot:'.$key, $time = Chronos::now()->getTimestamp(), json_encode([
                'throughput' => $data['throughput'],
                'runtime' => $data['runtime'],
                'wait' => resolve(WaitTimeCalculator::class)->calculateFor($queue),
                'time' => $time,
            ])
        );

        $this->connection()->zremrangebyrank(
            'snapshot:'.$key, 0, -25
        );
    }

    /**
     * Get the base snapshot data for a given key.
     *
     * @param  string  $key
     * @return array
     */
    protected function baseSnapshotData($key)
    {
        $responses = $this->connection()->transaction(function ($trans) use ($key) {
            $trans->hmget($key, 'throughput', 'runtime');

            $trans->del($key);
        });

        return [
            'throughput' => $responses[0][0],
            'runtime' => $responses[0][1],
        ];
    }

    /**
     * Get the number of minutes passed since the last snapshot.
     *
     * @return int
     */
    protected function minutesSinceLastSnapshot()
    {
        $lastSnapshotAt = $this->connection()->get('last_snapshot_at')
                    ?: $this->storeSnapshotTimestamp();

        return round(max(
            (Chronos::now()->getTimestamp() - $lastSnapshotAt) / 60, 1
        ));
    }

    /**
     * Store the current timestamp as the "last snapshot timestamp".
     *
     * @return int
     */
    protected function storeSnapshotTimestamp()
    {
        return tap(Chronos::now()->getTimestamp(), function ($timestamp) {
            $this->connection()->set('last_snapshot_at', $timestamp);
        });
    }

    /**
     * Attempt to aquire a lock to monitor the queue wait times.
     *
     * @return bool
     */
    public function acquireWaitTimeMonitorLock()
    {
        return resolve(Lock::class)->get('monitor:time-to-clear');
    }

    /**
     * Clear the metrics for a key.
     *
     * @param  string  $key
     * @return void
     */
    public function forget($key)
    {
        $this->connection()->del($key);
    }

    /**
     * Get the Redis connection instance.
     *
     * @return \Illuminate\Redis\Connetions\Connection
     */
    public function connection()
    {
        return $this->redis->connection('horizon-metrics');
    }
}
