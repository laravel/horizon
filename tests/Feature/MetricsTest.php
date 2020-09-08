<?php

namespace Laravel\Horizon\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Stopwatch;
use Laravel\Horizon\Tests\IntegrationTest;
use Mockery;

class MetricsTest extends IntegrationTest
{
    public function test_total_throughput_is_stored()
    {
        Queue::push(new Jobs\BasicJob);
        Queue::push(new Jobs\BasicJob);

        $this->work();
        $this->work();

        $this->assertSame(2, resolve(MetricsRepository::class)->throughput());
    }

    public function test_throughput_is_stored_per_job_class()
    {
        Queue::push(new Jobs\BasicJob);
        Queue::push(new Jobs\BasicJob);
        Queue::push(new Jobs\BasicJob);
        Queue::push(new Jobs\ConditionallyFailingJob);

        $this->work();
        $this->work();
        $this->work();
        $this->work();

        $this->assertSame(4, resolve(MetricsRepository::class)->throughput());
        $this->assertSame(3, resolve(MetricsRepository::class)->throughputForJob(Jobs\BasicJob::class));
        $this->assertSame(1, resolve(MetricsRepository::class)->throughputForJob(Jobs\ConditionallyFailingJob::class));
    }

    public function test_throughput_is_stored_per_queue()
    {
        Queue::push(new Jobs\BasicJob);
        Queue::push(new Jobs\BasicJob);
        Queue::push(new Jobs\BasicJob);
        Queue::push(new Jobs\ConditionallyFailingJob);

        $this->work();
        $this->work();
        $this->work();
        $this->work();

        $this->assertSame(4, resolve(MetricsRepository::class)->throughput());
        $this->assertSame(4, resolve(MetricsRepository::class)->throughputForQueue('default'));
    }

    public function test_average_runtime_is_stored_per_job_class_in_milliseconds()
    {
        $stopwatch = Mockery::mock(Stopwatch::class);
        $stopwatch->shouldReceive('start');
        $stopwatch->shouldReceive('check')->andReturn(1, 2);
        $this->app->instance(Stopwatch::class, $stopwatch);

        Queue::push(new Jobs\BasicJob);
        Queue::push(new Jobs\BasicJob);

        $this->work();
        $this->work();

        $this->assertSame(1.5, resolve(MetricsRepository::class)->runtimeForJob(Jobs\BasicJob::class));
    }

    public function test_average_runtime_is_stored_per_queue_in_milliseconds()
    {
        $stopwatch = Mockery::mock(Stopwatch::class);
        $stopwatch->shouldReceive('start');
        $stopwatch->shouldReceive('check')->andReturn(1, 2);
        $this->app->instance(Stopwatch::class, $stopwatch);

        Queue::push(new Jobs\BasicJob);
        Queue::push(new Jobs\BasicJob);

        $this->work();
        $this->work();

        $this->assertSame(1.5, resolve(MetricsRepository::class)->runtimeForQueue('default'));
    }

    public function test_list_of_all_jobs_with_metric_information_is_maintained()
    {
        Queue::push(new Jobs\BasicJob);
        Queue::push(new Jobs\ConditionallyFailingJob);

        $this->work();
        $this->work();

        $jobs = resolve(MetricsRepository::class)->measuredJobs();
        $this->assertCount(2, $jobs);
        $this->assertContains(Jobs\ConditionallyFailingJob::class, $jobs);
        $this->assertContains(Jobs\BasicJob::class, $jobs);
    }

    public function test_snapshot_of_metrics_performance_can_be_stored()
    {
        $stopwatch = Mockery::mock(Stopwatch::class);
        $stopwatch->shouldReceive('start');
        $stopwatch->shouldReceive('check')->andReturn(1, 2, 3);
        $this->app->instance(Stopwatch::class, $stopwatch);

        Queue::push(new Jobs\BasicJob);
        Queue::push(new Jobs\BasicJob);

        // Run first two jobs...
        $this->work();
        $this->work();

        // Take initial snapshot and set initial timestamp...
        CarbonImmutable::setTestNow($firstTimestamp = CarbonImmutable::now());
        resolve(MetricsRepository::class)->snapshot();

        // Work another job and take another snapshot...
        Queue::push(new Jobs\BasicJob);
        $this->work();
        CarbonImmutable::setTestNow(CarbonImmutable::now()->addSeconds(1));
        resolve(MetricsRepository::class)->snapshot();

        $snapshots = resolve(MetricsRepository::class)->snapshotsForJob(Jobs\BasicJob::class);

        // Test job snapshots...
        $this->assertEquals([
            (object) [
                'throughput' => 2,
                'runtime' => 1.5,
                'time' => $firstTimestamp->getTimestamp(),
            ],
            (object) [
                'throughput' => 1,
                'runtime' => 3,
                'time' => CarbonImmutable::now()->getTimestamp(),
            ],
        ], $snapshots);

        // Test queue snapshots...
        $snapshots = resolve(MetricsRepository::class)->snapshotsForQueue('default');
        $this->assertEquals([
            (object) [
                'throughput' => 2,
                'runtime' => 1.5,
                'wait' => 0,
                'time' => $firstTimestamp->getTimestamp(),
            ],
            (object) [
                'throughput' => 1,
                'runtime' => 3,
                'wait' => 0,
                'time' => CarbonImmutable::now()->getTimestamp(),
            ],
        ], $snapshots);
    }

    public function test_jobs_processed_per_minute_since_last_snapshot_is_calculable()
    {
        $stopwatch = Mockery::mock(Stopwatch::class);
        $stopwatch->shouldReceive('start');
        $stopwatch->shouldReceive('check')->andReturn(1);
        $this->app->instance(Stopwatch::class, $stopwatch);

        Queue::push(new Jobs\BasicJob);
        Queue::push(new Jobs\BasicJob);

        // Run first two jobs...
        $this->work();
        $this->work();

        $this->assertSame(
            2.0, resolve(MetricsRepository::class)->jobsProcessedPerMinute()
        );

        // Adjust current time...
        CarbonImmutable::setTestNow(CarbonImmutable::now()->addMinutes(2));

        $this->assertSame(
            1.0, resolve(MetricsRepository::class)->jobsProcessedPerMinute()
        );

        // take snapshot and ensure count is reset...
        resolve(MetricsRepository::class)->snapshot();

        $this->assertSame(
            0.0, resolve(MetricsRepository::class)->jobsProcessedPerMinute()
        );
    }

    public function test_only_past_24_snapshots_are_retained()
    {
        $stopwatch = Mockery::mock(Stopwatch::class);
        $stopwatch->shouldReceive('start');
        $stopwatch->shouldReceive('check')->andReturn(1);
        $this->app->instance(Stopwatch::class, $stopwatch);

        CarbonImmutable::setTestNow(CarbonImmutable::now());

        // Run the jobs...
        for ($i = 0; $i < 30; $i++) {
            Queue::push(new Jobs\BasicJob);
            $this->work();
            resolve(MetricsRepository::class)->snapshot();
            CarbonImmutable::setTestNow(CarbonImmutable::now()->addSeconds(1));
        }

        // Check the job snapshots...
        $snapshots = resolve(MetricsRepository::class)->snapshotsForJob(Jobs\BasicJob::class);
        $this->assertCount(24, $snapshots);
        $this->assertSame(CarbonImmutable::now()->getTimestamp() - 1, $snapshots[23]->time);

        // Check the queue snapshots...
        $snapshots = resolve(MetricsRepository::class)->snapshotsForQueue('default');
        $this->assertCount(24, $snapshots);
        $this->assertSame(CarbonImmutable::now()->getTimestamp() - 1, $snapshots[23]->time);

        CarbonImmutable::setTestNow();
    }
}
