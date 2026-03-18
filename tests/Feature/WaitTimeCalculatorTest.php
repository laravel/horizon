<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Supervisor;
use Laravel\Horizon\SupervisorOptions;
use Laravel\Horizon\Tests\IntegrationTest;
use Laravel\Horizon\WaitTimeCalculator;
use Mockery;

class WaitTimeCalculatorTest extends IntegrationTest
{
    public function test_time_to_clear_is_calculated_per_queue()
    {
        $calculator = $this->with_scenario([
            'test-supervisor' => (object) [
                'processes' => [
                    'redis:test-queue' => 1,
                ],
            ],
            'test-supervisor-2' => (object) [
                'processes' => [
                    'redis:test-queue' => 1,
                ],
            ],
        ], [
            'test-queue' => [
                'size' => 10,
                'runtime' => 1000,
            ],
        ]);

        $this->assertEquals(
            ['redis:test-queue' => 5],
            $calculator->calculate()
        );
    }

    public function test_multiple_queues_are_supported()
    {
        $calculator = $this->with_scenario([
            'test-supervisor' => (object) [
                'processes' => [
                    'redis:test-queue' => 2,
                ],
            ],
            'test-supervisor-2' => (object) [
                'processes' => [
                    'redis:test-queue-2' => 1,
                ],
            ],
        ], [
            'test-queue' => [
                'size' => 10,
                'runtime' => 1000,
            ],
            'test-queue-2' => [
                'size' => 20,
                'runtime' => 2000,
            ],
        ]);

        $this->assertEquals(
            ['redis:test-queue' => 5, 'redis:test-queue-2' => 40],
            $calculator->calculate()
        );

        // Test easily retrieving the longest wait...
        $this->assertEquals(
            ['redis:test-queue-2' => 40], collect($calculator->calculate())->take(1)->all()
        );
    }

    public function test_single_queue_can_be_retrieved_for_multiple_queues()
    {
        $calculator = $this->with_scenario([
            'test-supervisor' => (object) [
                'processes' => [
                    'redis:test-queue' => 2,
                ],
            ],
            'test-supervisor-2' => (object) [
                'processes' => [
                    'redis:test-queue-2' => 1,
                ],
            ],
        ], [
            'test-queue' => [
                'size' => 10,
                'runtime' => 1000,
            ],
            'test-queue-2' => [
                'size' => 20,
                'runtime' => 2000,
            ],
        ]);

        $this->assertEquals(
            ['redis:test-queue-2' => 40],
            $calculator->calculate('redis:test-queue-2')
        );

        $this->assertSame(
            40.0,
            $calculator->calculateFor('redis:test-queue-2')
        );
    }

    public function test_time_to_clear_can_be_zero()
    {
        $calculator = $this->with_scenario([
            'test-supervisor' => (object) [
                'processes' => [
                    'redis:test-queue' => 1,
                ],
            ],
        ], [
            'test-queue' => [
                'size' => 0,
                'runtime' => 1000,
            ],
        ]);

        $this->assertEquals(
            ['redis:test-queue' => 0],
            $calculator->calculate()
        );
    }

    public function test_total_processes_can_be_zero()
    {
        $calculator = $this->with_scenario([
            'test-supervisor' => (object) [
                'processes' => [
                    'redis:test-queue' => 0,
                ],
            ],
        ], [
            'test-queue' => [
                'size' => 10,
                'runtime' => 1000,
            ],
        ]);

        $this->assertEquals(
            ['redis:test-queue' => 10],
            $calculator->calculate()
        );
    }

    public function test_calculate_for_supervisor_only_checks_supervisor_queues()
    {
        $calculator = $this->with_scenario([
            'redis-supervisor' => (object) [
                'processes' => [
                    'redis:test-queue' => 2,
                ],
            ],
            'rabbitmq-supervisor' => (object) [
                'processes' => [
                    'rabbitmq:other-queue' => 1,
                ],
            ],
        ], [
            'test-queue' => [
                'size' => 10,
                'runtime' => 1000,
            ],
            // Note: rabbitmq:other-queue is not set up on the mock queue factory,
            // so if calculateForSupervisor tries to access it, the test would fail.
        ]);

        $supervisor = Mockery::mock(Supervisor::class);
        $supervisor->options = new SupervisorOptions('redis-supervisor', 'redis', 'test-queue');

        $this->assertEquals(
            ['redis:test-queue' => 5],
            $calculator->calculateForSupervisor($supervisor)
        );
    }

    public function test_calculate_for_supervisor_with_multiple_queues()
    {
        $calculator = $this->with_scenario([
            'redis-supervisor' => (object) [
                'processes' => [
                    'redis:queue-a' => 2,
                    'redis:queue-b' => 1,
                ],
            ],
        ], [
            'queue-a' => [
                'size' => 10,
                'runtime' => 1000,
            ],
            'queue-b' => [
                'size' => 5,
                'runtime' => 2000,
            ],
        ]);

        $supervisor = Mockery::mock(Supervisor::class);
        $supervisor->options = new SupervisorOptions('redis-supervisor', 'redis', 'queue-a,queue-b');

        $results = $calculator->calculateForSupervisor($supervisor);

        $this->assertEquals(
            ['redis:queue-b' => 10, 'redis:queue-a' => 5],
            $results
        );
    }

    protected function with_scenario(array $supervisorSettings, array $queues)
    {
        $queue = Mockery::mock(QueueFactory::class);
        $supervisors = Mockery::mock(SupervisorRepository::class);
        $metrics = Mockery::mock(MetricsRepository::class);

        $supervisors->shouldReceive('all')->andReturn($supervisorSettings);
        $queue->shouldReceive('connection')->andReturnSelf();

        foreach ($queues as $name => $queueSettings) {
            $queue->shouldReceive('readyNow')->with($name)->andReturn($queueSettings['size']);
            $metrics->shouldReceive('runtimeForQueue')->with($name)->andReturn($queueSettings['runtime']);
        }

        return new WaitTimeCalculator($queue, $supervisors, $metrics);
    }
}
