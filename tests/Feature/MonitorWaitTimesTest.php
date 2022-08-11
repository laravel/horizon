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
        config(['horizon.waits' => []]);

        Event::fake();

        $calc = Mockery::mock(WaitTimeCalculator::class);
        $calc->expects('calculate')
            ->withNoArgs()
            ->andReturn([
                'redis:test-queue' => 10,
                'redis:test-queue-2' => 80,
            ]);
        $this->app->instance(WaitTimeCalculator::class, $calc);

        $listener = new MonitorWaitTimes(resolve(MetricsRepository::class));
        $listener->lastMonitoredAt = CarbonImmutable::now()->subDay();

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
        $calc->expects('calculate')
            ->withNoArgs()
            ->andReturn([
                'redis:ignore-queue' => 10,
            ]);
        $this->app->instance(WaitTimeCalculator::class, $calc);

        $listener = new MonitorWaitTimes(resolve(MetricsRepository::class));
        $listener->lastMonitoredAt = CarbonImmutable::now()->subDays(1);

        $listener->handle();

        Event::assertNotDispatched(LongWaitDetected::class);
    }

    public function test_monitors_specific_queue_with_long_wait()
    {
        config(['horizon.waits' => ['redis:default' => 0, 'redis:shared-1' => 10]]);

        Event::fake();

        $calc = Mockery::mock(WaitTimeCalculator::class);
        $calc->expects('calculate')
            ->withNoArgs()
            ->andReturn([
                'redis:default' => 90,
                'redis:shared-1,shared-2' => 25,
            ]);
        $calc->expects('calculateFor')
            ->with('redis:shared-1')
            ->andReturn(15);
        $this->app->instance(WaitTimeCalculator::class, $calc);

        $listener = new MonitorWaitTimes(resolve(MetricsRepository::class));
        $listener->lastMonitoredAt = CarbonImmutable::now()->subDay();

        $listener->handle();

        Event::assertDispatched(LongWaitDetected::class, function ($event) {
            return $event->connection == 'redis' && $event->queue == 'shared-1';
        });
    }
}
