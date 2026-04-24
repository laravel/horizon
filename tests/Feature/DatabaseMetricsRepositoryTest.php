<?php

namespace Laravel\Horizon\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Enums\MetricKind;
use Laravel\Horizon\Models\HorizonMetric;
use Laravel\Horizon\Models\HorizonMetricIncrement;
use Laravel\Horizon\Models\HorizonMetricSnapshot;
use Laravel\Horizon\Repositories\DatabaseMetricsRepository;
use Laravel\Horizon\Tests\DatabaseIntegrationTest;
use Laravel\Horizon\WaitTimeCalculator;
use Mockery;
use ReflectionMethod;

class DatabaseMetricsRepositoryTest extends DatabaseIntegrationTest
{
    protected function repo(): DatabaseMetricsRepository
    {
        return $this->app->make(MetricsRepository::class);
    }

    public function test_repository_is_database_implementation()
    {
        $this->assertInstanceOf(DatabaseMetricsRepository::class, $this->repo());
    }

    public function test_increment_job_stores_throughput_and_runtime()
    {
        $repo = $this->repo();

        $repo->incrementJob('App\\Jobs\\Foo', 1.0);
        $repo->incrementJob('App\\Jobs\\Foo', 3.0);

        $this->assertSame(2, $repo->throughputForJob('App\\Jobs\\Foo'));
        $this->assertSame(2.0, $repo->runtimeForJob('App\\Jobs\\Foo'));
    }

    public function test_increment_queue_stores_throughput_and_runtime()
    {
        $repo = $this->repo();

        $repo->incrementQueue('database:default', 1.0);
        $repo->incrementQueue('database:default', 2.0);

        $this->assertSame(2, $repo->throughputForQueue('database:default'));
        $this->assertSame(1.5, $repo->runtimeForQueue('database:default'));
    }

    public function test_throughput_sums_queue_throughputs()
    {
        $repo = $this->repo();

        $repo->incrementQueue('database:default', 10.0);
        $repo->incrementQueue('database:default', 20.0);
        $repo->incrementQueue('database:emails', 10.0);

        $this->assertSame(3, $repo->throughput());
    }

    public function test_measured_jobs_and_queues_strip_prefixes_and_sort()
    {
        $repo = $this->repo();

        $repo->incrementJob('App\\Jobs\\B', 1.0);
        $repo->incrementJob('App\\Jobs\\A', 1.0);
        $repo->incrementQueue('database:emails', 1.0);
        $repo->incrementQueue('database:default', 1.0);

        $this->assertSame(['App\\Jobs\\A', 'App\\Jobs\\B'], $repo->measuredJobs());
        $this->assertSame(['database:default', 'database:emails'], $repo->measuredQueues());
    }

    public function test_null_runtime_is_treated_as_zero()
    {
        $repo = $this->repo();

        $repo->incrementJob('App\\Jobs\\Foo', null);

        $this->assertSame(1, $repo->throughputForJob('App\\Jobs\\Foo'));
        $this->assertSame(0.0, $repo->runtimeForJob('App\\Jobs\\Foo'));
    }

    public function test_unknown_metrics_return_safe_zeros()
    {
        $repo = $this->repo();

        $this->assertSame(0, $repo->throughputForJob('Unknown'));
        $this->assertSame(0.0, $repo->runtimeForJob('Unknown'));
        $this->assertSame(0, $repo->throughputForQueue('database:missing'));
    }

    public function test_snapshot_resets_metrics_and_records_historical_rows()
    {
        $repo = $this->repo();

        $this->mockWaitTimeCalculator(0);

        $repo->incrementJob('App\\Jobs\\Foo', 2.0);
        $repo->incrementQueue('database:default', 2.0);

        CarbonImmutable::setTestNow($firstSnapshot = CarbonImmutable::now());
        $repo->snapshot();

        $this->assertSame(0, $repo->throughputForJob('App\\Jobs\\Foo'));
        $this->assertSame(0, $repo->throughputForQueue('database:default'));

        $jobSnapshots = $repo->snapshotsForJob('App\\Jobs\\Foo');
        $this->assertCount(1, $jobSnapshots);
        $this->assertSame(1, $jobSnapshots[0]->throughput);
        $this->assertSame(2.0, $jobSnapshots[0]->runtime);
        $this->assertSame($firstSnapshot->getTimestamp(), $jobSnapshots[0]->time);

        $queueSnapshots = $repo->snapshotsForQueue('database:default');
        $this->assertCount(1, $queueSnapshots);
        $this->assertEquals(0, $queueSnapshots[0]->wait);

        CarbonImmutable::setTestNow();
    }

    public function test_snapshots_are_trimmed_to_configured_limit()
    {
        config(['horizon.metrics.trim_snapshots.job' => 3]);

        $repo = $this->repo();

        $this->mockWaitTimeCalculator(0);

        CarbonImmutable::setTestNow(CarbonImmutable::now());

        for ($i = 0; $i < 5; $i++) {
            $repo->incrementJob('App\\Jobs\\Foo', 1.0);
            $repo->snapshot();
            CarbonImmutable::setTestNow(CarbonImmutable::now()->addSeconds(1));
        }

        $this->assertCount(3, $repo->snapshotsForJob('App\\Jobs\\Foo'));

        CarbonImmutable::setTestNow();
    }

    public function test_jobs_processed_per_minute_uses_last_snapshot_timestamp()
    {
        CarbonImmutable::setTestNow(CarbonImmutable::now());

        $repo = $this->repo();

        $this->mockWaitTimeCalculator(0);

        $repo->incrementQueue('database:default', 1.0);
        $repo->incrementQueue('database:default', 1.0);

        $this->assertSame(2.0, $repo->jobsProcessedPerMinute());

        CarbonImmutable::setTestNow(CarbonImmutable::now()->addMinutes(2));
        $this->assertSame(1.0, $repo->jobsProcessedPerMinute());

        $repo->snapshot();
        $this->assertSame(0.0, $repo->jobsProcessedPerMinute());

        CarbonImmutable::setTestNow();
    }

    public function test_snapshot_with_no_metric_row_is_noop()
    {
        $repo = $this->repo();
        $this->mockWaitTimeCalculator(0);

        CarbonImmutable::setTestNow(CarbonImmutable::now());
        $repo->snapshot();

        $this->assertSame(0, HorizonMetricSnapshot::count());
        $this->assertSame(0, HorizonMetric::count());

        CarbonImmutable::setTestNow();
    }

    public function test_snapshot_preserves_concurrent_increments_via_relative_reset()
    {
        $repo = $this->repo();

        $repo->incrementQueue('racing', 2.0);

        $fold = new ReflectionMethod($repo, 'foldIncrements');
        $fold->setAccessible(true);
        $fold->invoke($repo, 'queue:racing', \Laravel\Horizon\Enums\MetricKind::Queue);

        $metric = HorizonMetric::where('key', 'queue:racing')->first();
        $sampledThroughput = (int) $metric->throughput;
        $sampledRuntime = (float) $metric->runtime;

        $repo->incrementQueue('racing', 6.0);
        $fold->invoke($repo, 'queue:racing', \Laravel\Horizon\Enums\MetricKind::Queue);

        $reset = new ReflectionMethod($repo, 'resetMetric');
        $reset->setAccessible(true);
        $reset->invoke($repo, 'queue:racing', $sampledThroughput, $sampledRuntime);

        $after = HorizonMetric::where('key', 'queue:racing')->first();

        $this->assertSame(1, (int) $after->throughput);
        $this->assertEqualsWithDelta(4.0 - 2.0, (float) $after->runtime, 0.0001);
    }

    public function test_snapshot_reset_preserves_fractional_runtime()
    {
        $repo = $this->repo();

        $repo->incrementQueue('fractional', 1.25);

        $fold = new ReflectionMethod($repo, 'foldIncrements');
        $fold->setAccessible(true);
        $fold->invoke($repo, 'queue:fractional', \Laravel\Horizon\Enums\MetricKind::Queue);

        $metric = HorizonMetric::where('key', 'queue:fractional')->first();
        $sampledThroughput = (int) $metric->throughput;
        $sampledRuntime = (float) $metric->runtime;

        $repo->incrementQueue('fractional', 3.75);
        $fold->invoke($repo, 'queue:fractional', \Laravel\Horizon\Enums\MetricKind::Queue);

        $reset = new ReflectionMethod($repo, 'resetMetric');
        $reset->setAccessible(true);
        $reset->invoke($repo, 'queue:fractional', $sampledThroughput, $sampledRuntime);

        $after = HorizonMetric::where('key', 'queue:fractional')->first();

        $this->assertSame(1, (int) $after->throughput);
        $this->assertEqualsWithDelta(2.5 - 1.25, (float) $after->runtime, 0.0001);
    }

    public function test_queue_with_maximum_throughput_and_runtime()
    {
        $repo = $this->repo();
        $this->mockWaitTimeCalculator(0);

        $repo->incrementQueue('database:slow', 50.0);
        $repo->incrementQueue('database:fast', 1.0);
        $repo->incrementQueue('database:fast', 1.0);

        $repo->snapshot();

        $this->assertSame('database:slow', $repo->queueWithMaximumRuntime());
        $this->assertSame('database:fast', $repo->queueWithMaximumThroughput());
    }

    public function test_queue_with_maximum_returns_null_when_no_snapshots_exist()
    {
        $this->assertNull($this->repo()->queueWithMaximumThroughput());
        $this->assertNull($this->repo()->queueWithMaximumRuntime());
    }

    public function test_queue_with_maximum_uses_bounded_number_of_queries()
    {
        $repo = $this->repo();
        $this->mockWaitTimeCalculator(0);

        foreach (range(1, 6) as $i) {
            $repo->incrementQueue("q{$i}", (float) $i);
        }

        $repo->snapshot();

        DB::enableQueryLog();
        $repo->queueWithMaximumThroughput();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            2,
            count($queries),
            'queueWithMaximum must resolve in a constant number of queries regardless of queue count.'
        );
    }

    public function test_trim_snapshots_keeps_most_recent_rows()
    {
        $repo = $this->repo();

        $base = CarbonImmutable::now();

        for ($i = 0; $i < 30; $i++) {
            HorizonMetricSnapshot::create([
                'key' => 'queue:default',
                'kind' => \Laravel\Horizon\Enums\MetricKind::Queue,
                'throughput' => $i,
                'runtime' => 0.0,
                'recorded_at' => $base->addSeconds($i),
            ]);
        }

        $trim = new ReflectionMethod($repo, 'trimSnapshots');
        $trim->setAccessible(true);
        $trim->invoke($repo, 'queue:default', 24);

        $this->assertSame(24, HorizonMetricSnapshot::where('key', 'queue:default')->count());

        $remaining = HorizonMetricSnapshot::where('key', 'queue:default')
            ->orderBy('recorded_at')
            ->pluck('throughput')
            ->all();

        $this->assertSame(range(6, 29), array_map('intval', $remaining));
    }

    public function test_trim_snapshots_is_noop_when_under_limit()
    {
        $repo = $this->repo();

        for ($i = 0; $i < 10; $i++) {
            HorizonMetricSnapshot::create([
                'key' => 'queue:default',
                'kind' => \Laravel\Horizon\Enums\MetricKind::Queue,
                'throughput' => $i,
                'runtime' => 0.0,
                'recorded_at' => CarbonImmutable::now()->addSeconds($i),
            ]);
        }

        $trim = new ReflectionMethod($repo, 'trimSnapshots');
        $trim->setAccessible(true);
        $trim->invoke($repo, 'queue:default', 24);

        $this->assertSame(10, HorizonMetricSnapshot::where('key', 'queue:default')->count());
    }

    public function test_trim_snapshots_with_zero_limit_deletes_every_row()
    {
        $repo = $this->repo();

        for ($i = 0; $i < 5; $i++) {
            HorizonMetricSnapshot::create([
                'key' => 'queue:default',
                'kind' => \Laravel\Horizon\Enums\MetricKind::Queue,
                'throughput' => $i,
                'runtime' => 0.0,
                'recorded_at' => CarbonImmutable::now()->addSeconds($i),
            ]);
        }

        $trim = new ReflectionMethod($repo, 'trimSnapshots');
        $trim->setAccessible(true);
        $trim->invoke($repo, 'queue:default', 0);

        $this->assertSame(0, HorizonMetricSnapshot::where('key', 'queue:default')->count());
    }

    public function test_clear_wipes_all_metric_data()
    {
        $repo = $this->repo();
        $this->mockWaitTimeCalculator(0);

        $repo->incrementJob('App\\Jobs\\Foo', 1.0);
        $repo->incrementQueue('database:default', 1.0);
        $repo->snapshot();

        $repo->clear();

        $this->assertSame([], $repo->measuredJobs());
        $this->assertSame([], $repo->measuredQueues());
        $this->assertSame([], $repo->snapshotsForJob('App\\Jobs\\Foo'));
        $this->assertSame([], $repo->snapshotsForQueue('database:default'));
    }

    public function test_forget_removes_specific_metric()
    {
        $repo = $this->repo();

        $repo->incrementJob('App\\Jobs\\Foo', 1.0);
        $repo->incrementJob('App\\Jobs\\Bar', 1.0);

        $repo->forget('job:App\\Jobs\\Foo');

        $this->assertSame(0, $repo->throughputForJob('App\\Jobs\\Foo'));
        $this->assertSame(1, $repo->throughputForJob('App\\Jobs\\Bar'));
    }

    public function test_acquire_wait_time_monitor_lock_is_exclusive_within_lock_window()
    {
        $repo = $this->repo();

        $this->assertTrue($repo->acquireWaitTimeMonitorLock());
        $this->assertFalse($repo->acquireWaitTimeMonitorLock());
    }

    public function test_increment_writes_to_append_only_increments_and_leaves_metrics_empty()
    {
        $this->repo()->incrementJob('Foo', 15.0);

        $row = HorizonMetricIncrement::where('key', 'job:Foo')->first();

        $this->assertNotNull($row);
        $this->assertSame(MetricKind::Job, $row->kind);
        $this->assertEqualsWithDelta(15.0, $row->runtime, 0.0001);
        $this->assertSame(0, HorizonMetric::count(), 'incrementMetric must not touch horizon_metrics.');
    }

    public function test_fold_preserves_late_writes_via_id_guard()
    {
        $repo = $this->repo();

        $repo->incrementQueue('default', 2.0);
        $repo->incrementQueue('default', 4.0);

        $maxIdBefore = (int) HorizonMetricIncrement::where('key', 'queue:default')->max('id');

        $repo->incrementQueue('default', 99.0);

        HorizonMetricIncrement::where('key', 'queue:default')
            ->where('id', '<=', $maxIdBefore)
            ->delete();

        $this->assertSame(
            1,
            HorizonMetricIncrement::where('key', 'queue:default')->count(),
            'Only the late-arriving row (id > maxIdBefore) must survive the id-guarded delete.'
        );

        $survivor = HorizonMetricIncrement::where('key', 'queue:default')->first();
        $this->assertEqualsWithDelta(99.0, $survivor->runtime, 0.0001);
    }

    protected function mockWaitTimeCalculator(float $wait): void
    {
        $calc = Mockery::mock(WaitTimeCalculator::class);
        $calc->shouldReceive('calculateFor')->andReturn($wait);
        $this->app->instance(WaitTimeCalculator::class, $calc);
    }
}
