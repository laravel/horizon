<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Laravel\Horizon\AutoScaler;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Supervisor;
use Laravel\Horizon\SupervisorOptions;
use Laravel\Horizon\SystemProcessCounter;
use Laravel\Horizon\Tests\IntegrationTest;
use Mockery;

class AutoScalerTest extends IntegrationTest
{
    public function test_scaler_attempts_to_get_closer_to_proper_balance_on_each_iteration()
    {
        [$scaler, $supervisor] = $this->with_scaling_scenario(20, [
            'first' => ['current' => 10, 'size' => 20, 'runtime' => 10],
            'second' => ['current' => 10, 'size' => 10, 'runtime' => 10],
        ]);

        $scaler->scale($supervisor);

        $this->assertSame(11, $supervisor->processPools['first']->totalProcessCount());
        $this->assertSame(9, $supervisor->processPools['second']->totalProcessCount());

        $scaler->scale($supervisor);

        $this->assertSame(12, $supervisor->processPools['first']->totalProcessCount());
        $this->assertSame(8, $supervisor->processPools['second']->totalProcessCount());

        $scaler->scale($supervisor);

        $this->assertSame(13, $supervisor->processPools['first']->totalProcessCount());
        $this->assertSame(7, $supervisor->processPools['second']->totalProcessCount());

        // Assert scaler stays at target values...
        $scaler->scale($supervisor);

        $this->assertSame(13, $supervisor->processPools['first']->totalProcessCount());
        $this->assertSame(7, $supervisor->processPools['second']->totalProcessCount());
    }

    public function test_balance_stays_even_when_queue_is_empty()
    {
        [$scaler, $supervisor] = $this->with_scaling_scenario(10, [
            'first' => ['current' => 5, 'size' => 0, 'runtime' => 0],
            'second' => ['current' => 5, 'size' => 0, 'runtime' => 0],
        ]);

        $scaler->scale($supervisor);

        $this->assertSame(4, $supervisor->processPools['first']->totalProcessCount());
        $this->assertSame(4, $supervisor->processPools['second']->totalProcessCount());

        $scaler->scale($supervisor);

        $this->assertSame(3, $supervisor->processPools['first']->totalProcessCount());
        $this->assertSame(3, $supervisor->processPools['second']->totalProcessCount());

        $scaler->scale($supervisor);

        $this->assertSame(2, $supervisor->processPools['first']->totalProcessCount());
        $this->assertSame(2, $supervisor->processPools['second']->totalProcessCount());

        $scaler->scale($supervisor);

        $this->assertSame(1, $supervisor->processPools['first']->totalProcessCount());
        $this->assertSame(1, $supervisor->processPools['second']->totalProcessCount());
    }

    public function test_balancer_assigns_more_processes_on_busy_queue()
    {
        [$scaler, $supervisor] = $this->with_scaling_scenario(10, [
            'first' => ['current' => 1, 'size' => 50, 'runtime' => 50],
            'second' => ['current' => 1, 'size' => 0, 'runtime' => 0],
        ]);

        $scaler->scale($supervisor);
        $scaler->scale($supervisor);

        $this->assertSame(3, $supervisor->processPools['first']->totalProcessCount());
        $this->assertSame(1, $supervisor->processPools['second']->totalProcessCount());

        $scaler->scale($supervisor);
        $scaler->scale($supervisor);

        $this->assertSame(5, $supervisor->processPools['first']->totalProcessCount());
        $this->assertSame(1, $supervisor->processPools['second']->totalProcessCount());

        $scaler->scale($supervisor);
        $scaler->scale($supervisor);

        $this->assertSame(7, $supervisor->processPools['first']->totalProcessCount());
        $this->assertSame(1, $supervisor->processPools['second']->totalProcessCount());

        $scaler->scale($supervisor);
        $scaler->scale($supervisor);
        $scaler->scale($supervisor);

        $this->assertSame(9, $supervisor->processPools['first']->totalProcessCount());
        $this->assertSame(1, $supervisor->processPools['second']->totalProcessCount());
    }

    public function test_balancing_a_single_queue_assigns_it_the_min_workers_with_empty_queue()
    {
        [$scaler, $supervisor] = $this->with_scaling_scenario(5, [
            'first' => ['current' => 2, 'size' => 0, 'runtime' => 0],
        ]);

        $scaler->scale($supervisor);
        $this->assertSame(1, $supervisor->processPools['first']->totalProcessCount());
    }

    public function test_scaler_will_not_scale_past_max_process_threshold_under_high_load()
    {
        [$scaler, $supervisor] = $this->with_scaling_scenario(20, [
            'first' => ['current' => 10, 'size' => 100, 'runtime' => 50],
            'second' => ['current' => 10, 'size' => 100, 'runtime' => 50],
        ]);

        $scaler->scale($supervisor);

        $this->assertSame(10, $supervisor->processPools['first']->totalProcessCount());
        $this->assertSame(10, $supervisor->processPools['second']->totalProcessCount());
    }

    public function test_scaler_will_not_scale_below_minimum_worker_threshold()
    {
        $external = Mockery::mock(SystemProcessCounter::class);
        $external->shouldReceive('get')->with('name')->andReturn(5);
        $this->app->instance(SystemProcessCounter::class, $external);

        [$scaler, $supervisor] = $this->with_scaling_scenario(5, [
            'first' => ['current' => 3, 'size' => 1000, 'runtime' => 50],
            'second' => ['current' => 2, 'size' => 1, 'runtime' => 1],
        ], ['minProcesses' => 2]);

        $scaler->scale($supervisor);

        $this->assertSame(3, $supervisor->processPools['first']->totalProcessCount());
        $this->assertSame(2, $supervisor->processPools['second']->totalProcessCount());

        $scaler->scale($supervisor);

        $this->assertSame(3, $supervisor->processPools['first']->totalProcessCount());
        $this->assertSame(2, $supervisor->processPools['second']->totalProcessCount());
    }

    /**
     * @return array{0: AutoScaler, 1: Supervisor}
     */
    protected function with_scaling_scenario($maxProcesses, array $pools, array $extraOptions = [])
    {
        // Mock dependencies...
        $queue = Mockery::mock(QueueFactory::class);
        $metrics = Mockery::mock(MetricsRepository::class);

        // Create scaler...
        $scaler = new Autoscaler($queue, $metrics);

        // Create Supervisor...
        $options = new SupervisorOptions('name', 'redis', 'default');
        $options->maxProcesses = $maxProcesses;
        $options->balance = 'auto';
        foreach ($extraOptions as $key => $value) {
            $options->{$key} = $value;
        }
        $supervisor = new Supervisor($options);

        // Create process pools...
        $supervisor->processPools = collect($pools)->mapWithKeys(function ($pool, $name) {
            return [$name => new Fakes\FakePool($name, $pool['current'])];
        });

        $queue->shouldReceive('connection')->with('redis')->andReturnSelf();

        // Set stats per pool...
        collect($pools)->each(function ($pool, $name) use ($queue, $metrics) {
            $queue->shouldReceive('readyNow')->with($name)->andReturn($pool['size']);
            $metrics->shouldReceive('runtimeForQueue')->with($name)->andReturn($pool['runtime']);
        });

        return [$scaler, $supervisor];
    }

    public function test_scaler_considers_max_shift_and_attempts_to_get_closer_to_proper_balance_on_each_iteration()
    {
        [$scaler, $supervisor] = $this->with_scaling_scenario(150, [
            'first' => ['current' => 75, 'size' => 600, 'runtime' => 75],
            'second' => ['current' => 75, 'size' => 300, 'runtime' => 75],
        ]);

        $supervisor->options->balanceMaxShift = 10;

        $scaler->scale($supervisor);

        $this->assertEquals(85, $supervisor->processPools['first']->totalProcessCount());
        $this->assertEquals(65, $supervisor->processPools['second']->totalProcessCount());

        $scaler->scale($supervisor);

        $this->assertEquals(95, $supervisor->processPools['first']->totalProcessCount());
        $this->assertEquals(55, $supervisor->processPools['second']->totalProcessCount());

        $scaler->scale($supervisor);

        $this->assertEquals(100, $supervisor->processPools['first']->totalProcessCount());
        $this->assertEquals(50, $supervisor->processPools['second']->totalProcessCount());

        // Assert scaler stays at target values...
        $scaler->scale($supervisor);

        $this->assertEquals(100, $supervisor->processPools['first']->totalProcessCount());
        $this->assertEquals(50, $supervisor->processPools['second']->totalProcessCount());

        // Assert scaler still stays at target values...
        $scaler->scale($supervisor);

        $this->assertEquals(100, $supervisor->processPools['first']->totalProcessCount());
        $this->assertEquals(50, $supervisor->processPools['second']->totalProcessCount());
    }

    public function test_scaler_does_not_permit_going_to_zero_processes_despite_exceeding_max_processes()
    {
        $external = Mockery::mock(SystemProcessCounter::class);
        $external->shouldReceive('get')->with('name')->andReturn(5);
        $this->app->instance(SystemProcessCounter::class, $external);

        [$scaler, $supervisor] = $this->with_scaling_scenario(15, [
            'first' => ['current' => 16, 'size' => 1, 'runtime' => 1],
            'second' => ['current' => 1, 'size' => 1, 'runtime' => 1],
        ], ['minProcesses' => 1]);

        $scaler->scale($supervisor);

        $this->assertSame(15, $supervisor->processPools['first']->totalProcessCount());
        $this->assertSame(1, $supervisor->processPools['second']->totalProcessCount());

        $scaler->scale($supervisor);

        $this->assertSame(14, $supervisor->processPools['first']->totalProcessCount());
        $this->assertSame(1, $supervisor->processPools['second']->totalProcessCount());
    }

    public function test_scaler_assigns_more_processes_to_queue_with_more_jobs_when_using_size_strategy()
    {
        [$scaler, $supervisor] = $this->with_scaling_scenario(100, [
            'first' => ['current' => 50, 'size' => 1000, 'runtime' => 10],
            'second' => ['current' => 50, 'size' => 500, 'runtime' => 1000],
        ], ['autoScalingStrategy' => 'size']);

        $scaler->scale($supervisor);

        $this->assertSame(51, $supervisor->processPools['first']->totalProcessCount());
        $this->assertSame(49, $supervisor->processPools['second']->totalProcessCount());

        $scaler->scale($supervisor);

        $this->assertSame(52, $supervisor->processPools['first']->totalProcessCount());
        $this->assertSame(48, $supervisor->processPools['second']->totalProcessCount());
    }

    public function test_scaler_works_with_a_single_process_pool()
    {
        [$scaler, $supervisor] = $this->with_scaling_scenario(10, [
            'default' => ['current' => 10, 'size' => 1, 'runtime' => 0],
        ], ['balance' => false]);

        $scaler->scale($supervisor);

        $this->assertSame(9, $supervisor->processPools['default']->totalProcessCount());

        [$scaler, $supervisor] = $this->with_scaling_scenario(10, [
            'default' => ['current' => 10, 'size' => 1, 'runtime' => 1000],
        ], ['balance' => false]);

        $scaler->scale($supervisor);

        $this->assertSame(9, $supervisor->processPools['default']->totalProcessCount());

        [$scaler, $supervisor] = $this->with_scaling_scenario(10, [
            'default' => ['current' => 5, 'size' => 11, 'runtime' => 1000],
        ], ['balance' => false]);

        $scaler->scale($supervisor);

        $this->assertSame(6, $supervisor->processPools['default']->totalProcessCount());
    }
}
