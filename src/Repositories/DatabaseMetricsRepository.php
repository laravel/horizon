<?php

namespace Laravel\Horizon\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Enums\MetricKind;
use Laravel\Horizon\Models\HorizonMetric;
use Laravel\Horizon\Models\HorizonMetricSnapshot;
use Laravel\Horizon\Models\HorizonState;
use Laravel\Horizon\WaitTimeCalculator;

class DatabaseMetricsRepository implements MetricsRepository
{
    public const LAST_SNAPSHOT_KEY = 'last_snapshot_at';

    /**
     * Get all of the class names that have metrics measurements.
     *
     * @return array
     */
    public function measuredJobs()
    {
        return $this->measured(MetricKind::Job);
    }

    /**
     * Get all of the queues that have metrics measurements.
     *
     * @return array
     */
    public function measuredQueues()
    {
        return $this->measured(MetricKind::Queue);
    }

    /**
     * Fetch the measured names of the given kind, stripping the prefix.
     *
     * @param  \Laravel\Horizon\Enums\MetricKind  $kind
     * @return array
     */
    protected function measured(MetricKind $kind)
    {
        return HorizonMetric::where('kind', $kind)
            ->orderBy('key')
            ->pluck('key')
            ->map(fn ($key) => preg_match('/'.$kind->value.':(.*)$/', $key, $m) ? $m[1] : $key)
            ->values()
            ->all();
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
        return (int) HorizonMetric::where('kind', MetricKind::Queue)->sum('throughput');
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
        $metric = HorizonMetric::where('key', $key)->first();

        return $metric ? (int) $metric->throughput : 0;
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
        $metric = HorizonMetric::where('key', $key)->first();

        return $metric ? (float) $metric->runtime : 0.0;
    }

    /**
     * Get the queue that has the longest runtime.
     *
     * @return string|null
     */
    public function queueWithMaximumRuntime()
    {
        return $this->queueWithMaximum('runtime');
    }

    /**
     * Get the queue that has the most throughput.
     *
     * @return string|null
     */
    public function queueWithMaximumThroughput()
    {
        return $this->queueWithMaximum('throughput');
    }

    /**
     * Find the queue whose latest snapshot has the maximum value for the given column.
     *
     * @param  string  $column
     * @return string|null
     */
    protected function queueWithMaximum($column)
    {
        $latest = HorizonMetricSnapshot::query()
            ->selectRaw('key, MAX(recorded_at) as max_recorded_at')
            ->where('kind', MetricKind::Queue)
            ->groupBy('key');

        $row = HorizonMetricSnapshot::query()
            ->from('horizon_metric_snapshots as s')
            ->joinSub($latest, 'latest', function ($join) {
                $join->on('latest.key', '=', 's.key')
                    ->on('latest.max_recorded_at', '=', 's.recorded_at');
            })
            ->orderByDesc('s.'.$column)
            ->orderByDesc('s.id')
            ->first(['s.key']);

        if ($row === null) {
            return null;
        }

        return preg_match('/^queue:(.*)$/', $row->key, $m) ? $m[1] : $row->key;
    }

    /**
     * Increment the metrics information for a job.
     *
     * @param  string  $job
     * @param  float|null  $runtime
     * @return void
     */
    public function incrementJob($job, $runtime)
    {
        $this->incrementMetric('job:'.$job, MetricKind::Job, $runtime);
    }

    /**
     * Increment the metrics information for a queue.
     *
     * @param  string  $queue
     * @param  float|null  $runtime
     * @return void
     */
    public function incrementQueue($queue, $runtime)
    {
        $this->incrementMetric('queue:'.$queue, MetricKind::Queue, $runtime);
    }

    /**
     * Increment the metrics for the given key atomically.
     *
     * @param  string  $key
     * @param  \Laravel\Horizon\Enums\MetricKind  $kind
     * @param  float|null  $runtime
     * @return void
     */
    protected function incrementMetric($key, MetricKind $kind, $runtime)
    {
        $sample = $runtime === null ? 0.0 : (float) $runtime;

        if ($this->applyIncrement($key, $sample) > 0) {
            return;
        }

        try {
            HorizonMetric::create([
                'key' => $key,
                'kind' => $kind,
                'throughput' => 1,
                'runtime' => $sample,
            ]);
        } catch (QueryException) {
            $this->applyIncrement($key, $sample);
        }
    }

    /**
     * Atomically update the throughput and rolling-average runtime for a key.
     *
     * @param  string  $key
     * @param  float  $sample
     * @return int Number of rows affected.
     */
    protected function applyIncrement($key, $sample)
    {
        return HorizonMetric::where('key', $key)->update([
            'throughput' => DB::raw('throughput + 1'),
            'runtime' => DB::raw(sprintf(
                '((throughput * runtime) + %F) / (throughput + 1)',
                (float) $sample
            )),
            'updated_at' => CarbonImmutable::now(),
        ]);
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
     * Get all of the snapshots for the given key in chronological order.
     *
     * @param  string  $key
     * @return array
     */
    protected function snapshotsFor($key)
    {
        return HorizonMetricSnapshot::where('key', $key)
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get()
            ->map(fn ($snap) => $this->presentSnapshot($snap))
            ->all();
    }

    /**
     * Map a snapshot model to the public shape expected by Horizon.
     *
     * @param  \Laravel\Horizon\Models\HorizonMetricSnapshot  $model
     * @return object
     */
    protected function presentSnapshot($model)
    {
        $data = [
            'throughput' => (int) $model->throughput,
            'runtime' => (float) $model->runtime,
        ];

        if ($model->kind === MetricKind::Queue) {
            $data['wait'] = $model->wait === null ? 0 : (float) $model->wait;
        }

        $data['time'] = $model->recorded_at->getTimestamp();

        return (object) $data;
    }

    /**
     * Store a snapshot of the metrics information.
     *
     * @return void
     */
    public function snapshot()
    {
        foreach ($this->measuredJobs() as $job) {
            $this->storeSnapshotForJob($job);
        }

        foreach ($this->measuredQueues() as $queue) {
            $this->storeSnapshotForQueue($queue);
        }

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
        $key = 'job:'.$job;
        $data = $this->baseSnapshotData($key);

        HorizonMetricSnapshot::create([
            'key' => $key,
            'kind' => MetricKind::Job,
            'throughput' => $data['throughput'],
            'runtime' => $data['runtime'],
            'recorded_at' => CarbonImmutable::now(),
        ]);

        $this->trimSnapshots($key, (int) config('horizon.metrics.trim_snapshots.job', 24));
    }

    /**
     * Store a snapshot for the given queue.
     *
     * @param  string  $queue
     * @return void
     */
    protected function storeSnapshotForQueue($queue)
    {
        $key = 'queue:'.$queue;
        $data = $this->baseSnapshotData($key);

        HorizonMetricSnapshot::create([
            'key' => $key,
            'kind' => MetricKind::Queue,
            'throughput' => $data['throughput'],
            'runtime' => $data['runtime'],
            'wait' => app(WaitTimeCalculator::class)->calculateFor($queue),
            'recorded_at' => CarbonImmutable::now(),
        ]);

        $this->trimSnapshots($key, (int) config('horizon.metrics.trim_snapshots.queue', 24));
    }

    /**
     * Read current metric values for the given key and reset them.
     *
     * @param  string  $key
     * @return array{throughput:int,runtime:float}
     */
    protected function baseSnapshotData($key)
    {
        $metric = HorizonMetric::where('key', $key)->first();

        if ($metric === null) {
            return ['throughput' => 0, 'runtime' => 0.0];
        }

        $sampled = [
            'throughput' => (int) $metric->throughput,
            'runtime' => (float) $metric->runtime,
        ];

        $this->resetMetric($key, $sampled['throughput'], $sampled['runtime']);

        return $sampled;
    }

    /**
     * Subtract the sampled throughput and runtime from a metric row atomically.
     *
     * @param  string  $key
     * @param  int  $sampledThroughput
     * @param  float  $sampledRuntime
     * @return void
     */
    protected function resetMetric($key, $sampledThroughput, $sampledRuntime)
    {
        HorizonMetric::where('key', $key)->update([
            'throughput' => DB::raw('throughput - '.(int) $sampledThroughput),
            'runtime' => DB::raw(sprintf('runtime - %F', (float) $sampledRuntime)),
            'updated_at' => CarbonImmutable::now(),
        ]);
    }

    /**
     * Keep only the most recent N snapshots for the given key.
     *
     * @param  string  $key
     * @param  int  $limit
     * @return void
     */
    protected function trimSnapshots($key, $limit)
    {
        $cutoffId = HorizonMetricSnapshot::where('key', $key)
            ->orderBy('recorded_at', 'desc')
            ->orderBy('id', 'desc')
            ->skip(max($limit, 0))
            ->take(1)
            ->value('id');

        if ($cutoffId === null) {
            return;
        }

        HorizonMetricSnapshot::where('key', $key)
            ->where('id', '<=', $cutoffId)
            ->delete();
    }

    /**
     * Get the number of minutes passed since the last snapshot.
     *
     * @return float
     */
    protected function minutesSinceLastSnapshot()
    {
        $state = HorizonState::where('key', self::LAST_SNAPSHOT_KEY)->first();

        $lastSnapshotAt = $state ? (int) $state->value : $this->storeSnapshotTimestamp();

        return max(
            (CarbonImmutable::now()->getTimestamp() - $lastSnapshotAt) / 60,
            1
        );
    }

    /**
     * Store the current timestamp as the "last snapshot timestamp".
     *
     * @return int
     */
    protected function storeSnapshotTimestamp()
    {
        $timestamp = CarbonImmutable::now()->getTimestamp();

        HorizonState::updateOrCreate(
            ['key' => self::LAST_SNAPSHOT_KEY],
            ['value' => (string) $timestamp]
        );

        return $timestamp;
    }

    /**
     * Attempt to acquire a lock to monitor the queue wait times.
     *
     * @return bool
     */
    public function acquireWaitTimeMonitorLock()
    {
        return Cache::lock('horizon:monitor:time-to-clear', 60)->get();
    }

    /**
     * Clear the metrics for a key.
     *
     * @param  string  $key
     * @return void
     */
    public function forget($key)
    {
        HorizonMetric::where('key', $key)->delete();
    }

    /**
     * Delete all stored metrics information.
     *
     * @return void
     */
    public function clear()
    {
        HorizonMetric::truncate();
        HorizonMetricSnapshot::truncate();
        HorizonState::where('key', self::LAST_SNAPSHOT_KEY)->delete();
    }
}
