<?php

namespace Laravel\Horizon\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Enums\MetricKind;
use Laravel\Horizon\Models\HorizonMetric;
use Laravel\Horizon\Models\HorizonMetricIncrement;
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
        $persisted = HorizonMetric::where('kind', $kind)->pluck('key');
        $pending = HorizonMetricIncrement::where('kind', $kind)->distinct()->pluck('key');

        return $persisted->merge($pending)
            ->unique()
            ->sort()
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
        $persisted = (int) HorizonMetric::where('kind', MetricKind::Queue)->sum('throughput');
        $pending = (int) HorizonMetricIncrement::where('kind', MetricKind::Queue)->count();

        return $persisted + $pending;
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
        $persistedThroughput = $metric ? (int) $metric->throughput : 0;

        $pendingCount = (int) HorizonMetricIncrement::where('key', $key)->count();

        return $persistedThroughput + $pendingCount;
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
        $persistedThroughput = $metric ? (int) $metric->throughput : 0;
        $persistedRuntime = $metric ? (float) $metric->runtime : 0.0;

        $pending = HorizonMetricIncrement::where('key', $key)
            ->selectRaw('COUNT(*) as c, COALESCE(SUM(runtime), 0) as s')
            ->first();

        $pendingCount = $pending ? (int) $pending->c : 0;
        $pendingSum = $pending ? (float) $pending->s : 0.0;

        $totalCount = $persistedThroughput + $pendingCount;

        if ($totalCount === 0) {
            return 0.0;
        }

        return (($persistedThroughput * $persistedRuntime) + $pendingSum) / $totalCount;
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
     * Record a single sample into the append-only increments table.
     *
     * @param  string  $key
     * @param  \Laravel\Horizon\Enums\MetricKind  $kind
     * @param  float|null  $runtime
     * @return void
     */
    protected function incrementMetric($key, MetricKind $kind, $runtime)
    {
        HorizonMetricIncrement::create([
            'key' => $key,
            'kind' => $kind,
            'runtime' => $runtime === null ? 0.0 : (float) $runtime,
            'recorded_at' => CarbonImmutable::now(),
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
        $data = $this->baseSnapshotData($key, MetricKind::Job);

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
        $data = $this->baseSnapshotData($key, MetricKind::Queue);

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
     * Read and reset the current metric counters for the given key.
     *
     * @param  string  $key
     * @param  \Laravel\Horizon\Enums\MetricKind  $kind
     * @return array{throughput:int,runtime:float}
     */
    protected function baseSnapshotData($key, MetricKind $kind)
    {
        $this->foldIncrements($key, $kind);

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
     * Aggregate pending increments for the given key into the metric row.
     *
     * @param  string  $key
     * @param  \Laravel\Horizon\Enums\MetricKind  $kind
     * @return void
     */
    protected function foldIncrements($key, MetricKind $kind)
    {
        $maxId = HorizonMetricIncrement::where('key', $key)->max('id');

        if ($maxId === null) {
            return;
        }

        $pending = HorizonMetricIncrement::where('key', $key)
            ->where('id', '<=', $maxId)
            ->selectRaw('COUNT(*) as c, COALESCE(SUM(runtime), 0) as s')
            ->first();

        $pendingCount = (int) $pending->c;
        $pendingSum = (float) $pending->s;

        if ($pendingCount === 0) {
            HorizonMetricIncrement::where('key', $key)
                ->where('id', '<=', $maxId)
                ->delete();

            return;
        }

        $existing = HorizonMetric::where('key', $key)->first();
        $existingThroughput = $existing ? (int) $existing->throughput : 0;
        $existingRuntime = $existing ? (float) $existing->runtime : 0.0;

        $newThroughput = $existingThroughput + $pendingCount;
        $newRuntime = (($existingThroughput * $existingRuntime) + $pendingSum) / max($newThroughput, 1);

        $now = CarbonImmutable::now();

        HorizonMetric::upsert([[
            'key' => $key,
            'kind' => $kind->value,
            'throughput' => $newThroughput,
            'runtime' => $newRuntime,
            'updated_at' => $now,
            'created_at' => $existing ? $existing->created_at : $now,
        ]], ['key'], ['kind', 'throughput', 'runtime', 'updated_at']);

        HorizonMetricIncrement::where('key', $key)
            ->where('id', '<=', $maxId)
            ->delete();
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
        $now = CarbonImmutable::now();

        HorizonState::upsert([[
            'key' => self::LAST_SNAPSHOT_KEY,
            'value' => (string) $timestamp,
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['key'], ['value', 'updated_at']);

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
        HorizonMetricIncrement::where('key', $key)->delete();
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
        HorizonMetricIncrement::truncate();
        HorizonState::where('key', self::LAST_SNAPSHOT_KEY)->delete();
    }
}
