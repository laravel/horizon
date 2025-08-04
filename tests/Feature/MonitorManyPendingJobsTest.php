<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Events\ManyPendingJobsDetected;
use Laravel\Horizon\Listeners\MonitorManyPendingJobs;
use Laravel\Horizon\Lock;
use Laravel\Horizon\Tests\IntegrationTest;
use Mockery;

class MonitorManyPendingJobsTest extends IntegrationTest
{
    public function test_many_pending_jobs_dispatches_event()
    {
        config(['horizon.pending_jobs_monitor_threshold' => 70]);

        Event::fake();

        $jobRepo = Mockery::mock(JobRepository::class);
        $jobRepo->shouldReceive('countPending')->andReturn(80);
        $this->app->instance(JobRepository::class, $jobRepo);

        $listener = new MonitorManyPendingJobs();

        $listener->handle();

        Event::assertDispatched(ManyPendingJobsDetected::class, function ($event) {
            return $event->amountPendingJobs === 80;
        });
    }

    public function test_below_threshold_pending_jobs_does_not_dispatch_event()
    {
        config(['horizon.pending_jobs_monitor_threshold' => 70]);

        Event::fake();

        $jobRepo = Mockery::mock(JobRepository::class);
        $jobRepo->shouldReceive('countPending')->andReturn(65);
        $this->app->instance(JobRepository::class, $jobRepo);

        $listener = new MonitorManyPendingJobs();

        $listener->handle();

        Event::assertNotDispatched(ManyPendingJobsDetected::class);
    }

    public function test_threshold_set_to_0_never_dispatches_event()
    {
        config(['horizon.pending_jobs_monitor_threshold' => 0]);

        Event::fake();

        $jobRepo = Mockery::mock(JobRepository::class);
        $jobRepo->expects('countPending')->never();
        $this->app->instance(JobRepository::class, $jobRepo);

        $listener = new MonitorManyPendingJobs();

        $listener->handle();

        Event::assertNotDispatched(ManyPendingJobsDetected::class);
    }

    public function test_default_config__never_dispatches_event()
    {
        Event::fake();

        $jobRepo = Mockery::mock(JobRepository::class);
        $jobRepo->expects('countPending')->never();
        $this->app->instance(JobRepository::class, $jobRepo);

        $listener = new MonitorManyPendingJobs();

        $listener->handle();

        Event::assertNotDispatched(ManyPendingJobsDetected::class);
    }

    public function test_monitor_skips_when_lock_is_not_acquired()
    {
        config(['horizon.pending_jobs_monitor_threshold' => 10]);

        Event::fake();

        $jobRepo = Mockery::mock(JobRepository::class);
        $jobRepo->shouldReceive('countPending')->andReturn(100);
        $this->app->instance(JobRepository::class, $jobRepo);

        $lock = Mockery::mock(Lock::class);
        $lock->shouldReceive('get')->andReturn(false);
        $this->app->instance(Lock::class, $lock);

        $listener = new MonitorManyPendingJobs();

        $listener->handle();

        Event::assertNotDispatched(ManyPendingJobsDetected::class);
    }

    public function test_monitor_skips_when_not_due_to_monitor()
    {
        config(['horizon.pending_jobs_monitor_threshold' => 10]);

        Event::fake();

        $jobRepo = Mockery::mock(JobRepository::class);
        $jobRepo->shouldReceive('countPending')->andReturn(15);
        $this->app->instance(JobRepository::class, $jobRepo);

        $lock = Mockery::mock(Lock::class);
        $lock->shouldReceive('get')->andReturnTrue();
        $this->app->instance(Lock::class, $lock);

        $listener = new MonitorManyPendingJobs();

        // Set a very recent timestamp, so it's not due yet
        $listener->lastMonitored = now()->toImmutable();

        $listener->handle();

        Event::assertNotDispatched(ManyPendingJobsDetected::class);
    }
}
