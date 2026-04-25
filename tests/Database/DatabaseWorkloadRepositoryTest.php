<?php

namespace Laravel\Horizon\Tests\Database;

use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;
use Laravel\Horizon\Tests\Database\Fakes\SupervisorWithFakePool as Supervisor;
use Laravel\Horizon\SupervisorOptions;
use Laravel\Horizon\WaitTimeCalculator;
use Mockery;

class DatabaseWorkloadRepositoryTest extends DatabaseTestCase
{
    public function test_workload_describes_each_active_supervisor_queue()
    {
        $waitTime = Mockery::mock(WaitTimeCalculator::class);
        $waitTime->shouldReceive('calculate')->andReturn(['redis:default' => 12]);
        $this->app->instance(WaitTimeCalculator::class, $waitTime);

        resolve(SupervisorRepository::class)->update(
            new Supervisor(new SupervisorOptions('foo:supervisor-1', 'redis', 'default'))
        );

        $workload = collect(resolve(WorkloadRepository::class)->get())->keyBy('name');

        $this->assertCount(1, $workload);
        $this->assertSame(12, $workload['default']['wait']);
        $this->assertSame(0, $workload['default']['length']);
    }

    public function test_workload_returns_empty_array_when_no_wait_times_are_calculated()
    {
        $waitTime = Mockery::mock(WaitTimeCalculator::class);
        $waitTime->shouldReceive('calculate')->andReturn([]);
        $this->app->instance(WaitTimeCalculator::class, $waitTime);

        $this->assertSame([], resolve(WorkloadRepository::class)->get());
    }
}
