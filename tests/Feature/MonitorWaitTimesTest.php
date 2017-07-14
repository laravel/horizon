<?php

namespace Laravel\Horizon\Tests\Feature;

use Mockery;
use Cake\Chronos\Chronos;
use Laravel\Horizon\Supervisor;
use Illuminate\Support\Facades\Event;
use Laravel\Horizon\WaitTimeCalculator;
use Laravel\Horizon\Tests\IntegrationTest;
use Laravel\Horizon\Events\LongWaitDetected;
use Laravel\Horizon\Events\SupervisorLooped;
use Laravel\Horizon\Listeners\MonitorWaitTimes;
use Laravel\Horizon\Contracts\MetricsRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;

class MonitorWaitTimesTest extends IntegrationTest
{
    public function test_queues_with_long_waits_are_found()
    {
        Event::fake();

        $redis = Mockery::mock(RedisFactory::class);
        $redis->shouldReceive('setnx')->with('monitor:time-to-clear', 1)->andReturn(1);
        $redis->shouldReceive('expire')->with('monitor:time-to-clear', 60);

        $calc = Mockery::mock(WaitTimeCalculator::class);
        $calc->shouldReceive('calculate')->andReturn([
            'redis:test-queue' => 10,
            'redis:test-queue-2' => 80,
        ]);
        $this->app->instance(WaitTimeCalculator::class, $calc);

        $listener = new MonitorWaitTimes(resolve(MetricsRepository::class), $redis);
        $listener->lastMonitoredAt = Chronos::now()->subDays(1);

        $listener->handle(new SupervisorLooped(Mockery::mock(Supervisor::class)));

        Event::assertDispatched(LongWaitDetected::class, function ($event) {
            return $event->connection == 'redis' && $event->queue == 'test-queue-2';
        });
    }
}
