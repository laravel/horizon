<?php

namespace Laravel\Horizon\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Events\LongWaitDetected;
use Laravel\Horizon\Events\SupervisorLooped;
use Laravel\Horizon\Listeners\MonitorWaitTimes;
use Laravel\Horizon\Supervisor;
use Laravel\Horizon\SupervisorOptions;
use Laravel\Horizon\Tests\IntegrationTest;
use Laravel\Horizon\WaitTimeCalculator;
use Mockery;

class MonitorWaitTimesTest extends IntegrationTest
{
    public function test_queues_with_long_waits_are_found()
    {
        Event::fake();

        $calc = Mockery::mock(WaitTimeCalculator::class);
        $calc->shouldReceive('calculateForSupervisor')->andReturn([
            'redis:test-queue' => 10,
            'redis:test-queue-2' => 80,
        ]);
        $this->app->instance(WaitTimeCalculator::class, $calc);

        $listener = new MonitorWaitTimes(app(MetricsRepository::class));

        $listener->handle($this->supervisorLoopedEvent());

        Event::assertDispatched(LongWaitDetected::class, function ($event) {
            return $event->connection == 'redis' && $event->queue == 'test-queue-2';
        });
    }

    public function test_queue_ignores_long_waits()
    {
        config(['horizon.waits' => ['redis:ignore-queue' => 0]]);

        Event::fake();

        $calc = Mockery::mock(WaitTimeCalculator::class);
        $calc->expects('calculateForSupervisor')->andReturn([
            'redis:ignore-queue' => 10,
        ]);
        $this->app->instance(WaitTimeCalculator::class, $calc);

        $listener = new MonitorWaitTimes(app(MetricsRepository::class));

        $listener->handle($this->supervisorLoopedEvent());

        Event::assertNotDispatched(LongWaitDetected::class);
    }

    public function test_monitor_wait_times_skips_when_lock_is_not_acquired()
    {
        config(['horizon.waits' => ['redis:default' => 60]]);

        Event::fake();

        $calc = Mockery::mock(WaitTimeCalculator::class);
        $calc->expects('calculateForSupervisor')->never();
        $this->app->instance(WaitTimeCalculator::class, $calc);

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('acquireWaitTimeMonitorLock')->once()->andReturnFalse();
        $this->app->instance(MetricsRepository::class, $metrics);

        $listener = new MonitorWaitTimes($metrics);

        $listener->handle($this->supervisorLoopedEvent());

        Event::assertNotDispatched(LongWaitDetected::class);
    }

    public function test_monitor_wait_times_skips_when_not_due_to_monitor()
    {
        config(['horizon.waits' => ['redis:default' => 60]]);

        Event::fake();

        $calc = Mockery::mock(WaitTimeCalculator::class);
        $calc->expects('calculateForSupervisor')->never();
        $this->app->instance(WaitTimeCalculator::class, $calc);

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('acquireWaitTimeMonitorLock')->never();
        $this->app->instance(MetricsRepository::class, $metrics);

        $listener = new MonitorWaitTimes($metrics);
        $listener->lastMonitored = CarbonImmutable::now(); // Too soon

        $listener->handle($this->supervisorLoopedEvent());

        Event::assertNotDispatched(LongWaitDetected::class);
    }

    public function test_monitor_wait_times_skips_when_not_due_to_monitor_and_executes_after_2_minutes()
    {
        config(['horizon.waits' => ['redis:default' => 60]]);

        Event::fake();

        $calc = Mockery::mock(WaitTimeCalculator::class);
        $calc->expects('calculateForSupervisor')->once()->andReturn([
            'redis:default' => 70,
        ]);
        $this->app->instance(WaitTimeCalculator::class, $calc);

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('acquireWaitTimeMonitorLock')->once()->andReturnTrue();
        $this->app->instance(MetricsRepository::class, $metrics);

        $listener = new MonitorWaitTimes($metrics);
        $listener->lastMonitored = CarbonImmutable::now(); // Too soon

        $listener->handle($this->supervisorLoopedEvent());

        Event::assertNotDispatched(LongWaitDetected::class);

        CarbonImmutable::setTestNow(now()->addMinutes(2)); // Simulate time passing

        $listener->handle($this->supervisorLoopedEvent());

        Event::assertDispatched(LongWaitDetected::class);
    }

    public function test_monitor_wait_times_executes_once_when_called_twice()
    {
        config(['horizon.waits' => ['redis:default' => 60]]);

        Event::fake();

        $calc = Mockery::mock(WaitTimeCalculator::class);
        $calc->expects('calculateForSupervisor')->once()->andReturn([
            'redis:default' => 70,
        ]);
        $this->app->instance(WaitTimeCalculator::class, $calc);

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('acquireWaitTimeMonitorLock')->once()->andReturnTrue();
        $this->app->instance(MetricsRepository::class, $metrics);

        $listener = new MonitorWaitTimes($metrics);
        $listener->handle($this->supervisorLoopedEvent());
        // Call it again to ensure it doesn't execute twice
        $listener->handle($this->supervisorLoopedEvent());

        Event::assertDispatchedTimes(LongWaitDetected::class, 1);
    }

    /**
     * Create a SupervisorLooped event with a mocked supervisor.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @return \Laravel\Horizon\Events\SupervisorLooped
     */
    protected function supervisorLoopedEvent($connection = 'redis', $queue = 'default')
    {
        $supervisor = Mockery::mock(Supervisor::class);
        $supervisor->options = new SupervisorOptions('test-supervisor', $connection, $queue);

        return new SupervisorLooped($supervisor);
    }
}
