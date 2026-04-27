<?php

namespace Laravel\Horizon\Tests\Database;

use Carbon\CarbonImmutable;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\WaitTimeCalculator;
use Mockery;

class DatabaseMetricsRepositoryTest extends DatabaseTestCase
{
    public function test_throughput_can_be_incremented_and_aggregated()
    {
        $metrics = resolve(MetricsRepository::class);

        $metrics->incrementJob('App\\Jobs\\Foo', 100);
        $metrics->incrementJob('App\\Jobs\\Foo', 200);
        $metrics->incrementJob('App\\Jobs\\Bar', 50);
        $metrics->incrementQueue('default', 100);
        $metrics->incrementQueue('default', 300);

        $this->assertSame(2, $metrics->throughput());
        $this->assertSame(2, $metrics->throughputForJob('App\\Jobs\\Foo'));
        $this->assertSame(1, $metrics->throughputForJob('App\\Jobs\\Bar'));
        $this->assertSame(2, $metrics->throughputForQueue('default'));
        $this->assertSame(150.0, $metrics->runtimeForJob('App\\Jobs\\Foo'));
        $this->assertSame(200.0, $metrics->runtimeForQueue('default'));
    }

    public function test_measured_jobs_and_queues_are_listed()
    {
        $metrics = resolve(MetricsRepository::class);

        $metrics->incrementJob('App\\Jobs\\Foo', 1);
        $metrics->incrementJob('App\\Jobs\\Bar', 1);
        $metrics->incrementQueue('default', 1);
        $metrics->incrementQueue('emails', 1);

        $this->assertSame(['App\\Jobs\\Bar', 'App\\Jobs\\Foo'], $metrics->measuredJobs());
        $this->assertSame(['default', 'emails'], $metrics->measuredQueues());
    }

    public function test_snapshots_can_be_taken_and_retrieved()
    {
        $waitTime = Mockery::mock(WaitTimeCalculator::class);
        $waitTime->shouldReceive('calculateFor')->andReturn(0);
        $this->app->instance(WaitTimeCalculator::class, $waitTime);

        $metrics = resolve(MetricsRepository::class);

        $metrics->incrementJob('App\\Jobs\\Foo', 100);
        $metrics->incrementQueue('default', 100);

        CarbonImmutable::setTestNow($firstTimestamp = CarbonImmutable::now());
        $metrics->snapshot();

        $metrics->incrementJob('App\\Jobs\\Foo', 200);
        $metrics->incrementQueue('default', 200);

        CarbonImmutable::setTestNow(CarbonImmutable::now()->addSeconds(1));
        $metrics->snapshot();

        $jobSnapshots = $metrics->snapshotsForJob('App\\Jobs\\Foo');
        $this->assertCount(2, $jobSnapshots);
        $this->assertSame(1, $jobSnapshots[0]->throughput);
        $this->assertSame(100, $jobSnapshots[0]->runtime);
        $this->assertSame(1, $jobSnapshots[1]->throughput);
        $this->assertSame(200, $jobSnapshots[1]->runtime);

        $queueSnapshots = $metrics->snapshotsForQueue('default');
        $this->assertCount(2, $queueSnapshots);
        $this->assertSame(0, $queueSnapshots[0]->wait);
        $this->assertSame($firstTimestamp->getTimestamp(), $queueSnapshots[0]->time);

        CarbonImmutable::setTestNow();
    }

    public function test_metrics_can_be_forgotten()
    {
        $metrics = resolve(MetricsRepository::class);

        $metrics->incrementJob('App\\Jobs\\Foo', 100);
        $metrics->forget('job:App\\Jobs\\Foo');

        $this->assertSame(0, $metrics->throughputForJob('App\\Jobs\\Foo'));
    }

    public function test_measured_jobs_and_queues_union_live_and_snapshot_data()
    {
        $waitTime = Mockery::mock(WaitTimeCalculator::class);
        $waitTime->shouldReceive('calculateFor')->andReturn(0);
        $this->app->instance(WaitTimeCalculator::class, $waitTime);

        $metrics = resolve(MetricsRepository::class);

        $metrics->incrementJob('App\\Jobs\\Old', 100);
        $metrics->incrementQueue('archived', 100);
        $metrics->snapshot();

        $metrics->incrementJob('App\\Jobs\\New', 50);
        $metrics->incrementQueue('default', 50);

        $this->assertSame(['App\\Jobs\\New', 'App\\Jobs\\Old'], $metrics->measuredJobs());
        $this->assertSame(['archived', 'default'], $metrics->measuredQueues());
    }
}
