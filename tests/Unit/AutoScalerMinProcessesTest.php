<?php

namespace Laravel\Horizon\Tests\Unit;

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Laravel\Horizon\AutoScaler;
use Laravel\Horizon\Contracts\HorizonCommandQueue;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Supervisor;
use Laravel\Horizon\SupervisorOptions;
use Laravel\Horizon\Tests\Feature\Fakes\FakePool;
use Laravel\Horizon\Tests\UnitTest;
use Mockery;

class AutoScalerMinProcessesTest extends UnitTest
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create a minimal Laravel application container for Supervisor.
        $app = new \Illuminate\Foundation\Application;
        $commandQueue = Mockery::mock(HorizonCommandQueue::class);
        $commandQueue->shouldReceive('flush')->andReturnNull();
        $app->instance(HorizonCommandQueue::class, $commandQueue);
        \Illuminate\Foundation\Application::setInstance($app);
    }

    public function test_autoscaler_respects_min_processes_for_empty_queues_when_other_queues_have_work()
    {
        [$scaler, $supervisor] = $this->with_scaling_scenario(10, [
            'busy' => ['current' => 1, 'size' => 10000, 'runtime' => 10],
            'empty' => ['current' => 0, 'size' => 0, 'runtime' => 0],
        ], ['minProcesses' => 2]);

        // Run multiple scaling iterations to converge.
        for ($i = 0; $i < 20; $i++) {
            $scaler->scale($supervisor);
        }

        // The empty queue should never go below minProcesses.
        $this->assertGreaterThanOrEqual(
            2,
            $supervisor->processPools['empty']->totalProcessCount(),
            'Empty queue should have at least minProcesses workers'
        );
    }

    public function test_autoscaler_respects_min_processes_with_multiple_active_queues()
    {
        [$scaler, $supervisor] = $this->with_scaling_scenario(12, [
            'queue_a' => ['current' => 0, 'size' => 10000, 'runtime' => 10],
            'queue_b' => ['current' => 0, 'size' => 10000, 'runtime' => 10],
            'queue_c' => ['current' => 0, 'size' => 0, 'runtime' => 0],
        ], ['minProcesses' => 1]);

        for ($i = 0; $i < 30; $i++) {
            $scaler->scale($supervisor);
        }

        // The empty queue should have at least minProcesses.
        $this->assertGreaterThanOrEqual(
            1,
            $supervisor->processPools['queue_c']->totalProcessCount(),
            'Empty queue should have at least minProcesses workers'
        );
    }

    public function test_autoscaler_respects_min_processes_with_size_strategy()
    {
        [$scaler, $supervisor] = $this->with_scaling_scenario(10, [
            'busy' => ['current' => 1, 'size' => 10000, 'runtime' => 10],
            'empty' => ['current' => 0, 'size' => 0, 'runtime' => 0],
        ], ['minProcesses' => 2, 'autoScalingStrategy' => 'size']);

        for ($i = 0; $i < 20; $i++) {
            $scaler->scale($supervisor);
        }

        $this->assertGreaterThanOrEqual(
            2,
            $supervisor->processPools['empty']->totalProcessCount(),
            'Empty queue should have at least minProcesses workers with size strategy'
        );
    }

    /**
     * @return array{0: AutoScaler, 1: Supervisor}
     */
    protected function with_scaling_scenario($maxProcesses, array $pools, array $extraOptions = [])
    {
        $queue = Mockery::mock(QueueFactory::class);
        $metrics = Mockery::mock(MetricsRepository::class);

        $scaler = new AutoScaler($queue, $metrics);

        $options = new SupervisorOptions('name', 'redis', 'default');
        $options->maxProcesses = $maxProcesses;
        $options->balance = 'auto';
        foreach ($extraOptions as $key => $value) {
            $options->{$key} = $value;
        }
        $supervisor = new Supervisor($options);

        $supervisor->processPools = collect($pools)->mapWithKeys(function ($pool, $name) {
            return [$name => new FakePool($name, $pool['current'])];
        });

        $queue->shouldReceive('connection')->with('redis')->andReturnSelf();

        collect($pools)->each(function ($pool, $name) use ($queue, $metrics) {
            $queue->shouldReceive('readyNow')->with($name)->andReturn($pool['size']);
            $metrics->shouldReceive('runtimeForQueue')->with($name)->andReturn($pool['runtime']);
        });

        return [$scaler, $supervisor];
    }
}
