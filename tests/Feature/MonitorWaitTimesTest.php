<?php

namespace Laravel\Horizon\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Events\LongWaitDetected;
use Laravel\Horizon\Listeners\MonitorWaitTimes;
use Laravel\Horizon\Tests\IntegrationTest;
use Laravel\Horizon\WaitTimeCalculator;
use Mockery;

class MonitorWaitTimesTest extends IntegrationTest
{
    public function test_queues_with_long_waits_are_found()
    {
        Event::fake();

        $calc = Mockery::mock(WaitTimeCalculator::class);
        $calc->shouldReceive('calculate')->andReturn([
            'redis:test-queue' => 10,
            'redis:test-queue-2' => 80,
        ]);
        $this->app->instance(WaitTimeCalculator::class, $calc);

        $listener = new MonitorWaitTimes(app(MetricsRepository::class));

        $listener->handle();

        Event::assertDispatched(LongWaitDetected::class, function ($event) {
            return $event->connection == 'redis' && $event->queue == 'test-queue-2';
        });
    }

    public function test_queue_ignores_long_waits()
    {
        config(['horizon.waits' => ['redis:ignore-queue' => 0]]);

        Event::fake();

        $calc = Mockery::mock(WaitTimeCalculator::class);
        $calc->expects('calculate')->andReturn([
            'redis:ignore-queue' => 10,
        ]);
        $this->app->instance(WaitTimeCalculator::class, $calc);

        $listener = new MonitorWaitTimes(app(MetricsRepository::class));

        $listener->handle();

        Event::assertNotDispatched(LongWaitDetected::class);
    }

    public function test_monitor_wait_times_skips_when_lock_is_not_acquired()
    {
        config(['horizon.waits' => ['redis:default' => 60]]);

        Event::fake();

        $calc = Mockery::mock(WaitTimeCalculator::class);
        $calc->expects('calculate')->never();
        $this->app->instance(WaitTimeCalculator::class, $calc);

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('acquireWaitTimeMonitorLock')->once()->andReturnFalse();
        $this->app->instance(MetricsRepository::class, $metrics);

        $listener = new MonitorWaitTimes($metrics);

        $listener->handle();

        Event::assertNotDispatched(LongWaitDetected::class);
    }

    public function test_monitor_wait_times_skips_when_not_due_to_monitor()
    {
        config(['horizon.waits' => ['redis:default' => 60]]);

        Event::fake();

        $calc = Mockery::mock(WaitTimeCalculator::class);
        $calc->expects('calculate')->never();
        $this->app->instance(WaitTimeCalculator::class, $calc);

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('acquireWaitTimeMonitorLock')->never();
        $this->app->instance(MetricsRepository::class, $metrics);

        $listener = new MonitorWaitTimes($metrics);
        $listener->lastMonitored = CarbonImmutable::now(); // Too soon

        $listener->handle();

        Event::assertNotDispatched(LongWaitDetected::class);
    }

    public function test_monitor_wait_times_skips_when_not_due_to_monitor_and_executes_after_2_minutes()
    {
        config(['horizon.waits' => ['redis:default' => 60]]);

        Event::fake();

        $calc = Mockery::mock(WaitTimeCalculator::class);
        $calc->expects('calculate')->once()->andReturn([
            'redis:default' => 70,
        ]);
        $this->app->instance(WaitTimeCalculator::class, $calc);

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('acquireWaitTimeMonitorLock')->once()->andReturnTrue();
        $this->app->instance(MetricsRepository::class, $metrics);

        $listener = new MonitorWaitTimes($metrics);
        $listener->lastMonitored = CarbonImmutable::now(); // Too soon

        $listener->handle();

        Event::assertNotDispatched(LongWaitDetected::class);

        CarbonImmutable::setTestNow(now()->addMinutes(2)); // Simulate time passing

        $listener->handle();

        Event::assertDispatched(LongWaitDetected::class);
    }

    public function test_monitor_wait_times_executes_once_when_called_twice()
    {
        config(['horizon.waits' => ['redis:default' => 60]]);

        Event::fake();

        $calc = Mockery::mock(WaitTimeCalculator::class);
        $calc->expects('calculate')->once()->andReturn([
            'redis:default' => 70,
        ]);
        $this->app->instance(WaitTimeCalculator::class, $calc);

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('acquireWaitTimeMonitorLock')->once()->andReturnTrue();
        $this->app->instance(MetricsRepository::class, $metrics);

        $listener = new MonitorWaitTimes($metrics);
        $listener->handle();
        // Call it again to ensure it doesn't execute twice
        $listener->handle();

        Event::assertDispatchedTimes(LongWaitDetected::class, 1);
    }
}
