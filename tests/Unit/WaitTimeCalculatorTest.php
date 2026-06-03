<?php

namespace Laravel\Horizon\Tests\Unit;

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Tests\UnitTest;
use Laravel\Horizon\WaitTimeCalculator;
use Mockery;

class WaitTimeCalculatorTest extends UnitTest
{
    public function test_wait_times_can_be_calculated_for_a_single_connection()
    {
        $queue = Mockery::mock(QueueFactory::class);
        $supervisors = Mockery::mock(SupervisorRepository::class);
        $metrics = Mockery::mock(MetricsRepository::class);

        $supervisors->shouldReceive('all')->once()->andReturn([
            (object) [
                'options' => ['connection' => 'redis'],
                'processes' => ['redis:default' => 1],
            ],
            (object) [
                'options' => ['connection' => 'redis'],
                'processes' => ['redis:default' => 2],
            ],
            (object) [
                'options' => ['connection' => 'rabbitmq'],
                'processes' => ['rabbitmq:emails' => 1],
            ],
        ]);

        $queue->shouldReceive('connection')->once()->with('redis')->andReturnSelf();
        $queue->shouldReceive('connection')->with('rabbitmq')->never();
        $queue->shouldReceive('readyNow')->once()->with('default')->andReturn(10);
        $queue->shouldReceive('readyNow')->with('emails')->never();

        $metrics->shouldReceive('runtimeForQueue')->once()->with('default')->andReturn(1500);
        $metrics->shouldReceive('runtimeForQueue')->with('emails')->never();

        $calculator = new WaitTimeCalculator($queue, $supervisors, $metrics);

        $this->assertEquals(
            ['redis:default' => 5],
            $calculator->calculateForConnection('redis')
        );
    }
}
