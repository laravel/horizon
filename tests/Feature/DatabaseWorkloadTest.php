<?php

namespace Laravel\Horizon\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;
use Laravel\Horizon\DatabaseQueue;
use Laravel\Horizon\Events\LongWaitDetected;
use Laravel\Horizon\Listeners\MonitorWaitTimes;
use Laravel\Horizon\Tests\DatabaseIntegrationTest;
use Laravel\Horizon\WaitTimeCalculator;
use Mockery;

class DatabaseWorkloadTest extends DatabaseIntegrationTest
{
    public function test_ready_now_counts_only_available_unreserved_jobs()
    {
        Queue::push(new Jobs\BasicJob);
        Queue::push(new Jobs\BasicJob);

        $this->assertSame(2, $this->databaseQueue()->readyNow('default'));
    }

    public function test_ready_now_ignores_delayed_jobs()
    {
        Queue::push(new Jobs\BasicJob);
        Queue::later(60, new Jobs\BasicJob);

        $this->assertSame(1, $this->databaseQueue()->readyNow('default'));
    }

    public function test_ready_now_ignores_reserved_jobs()
    {
        Queue::push(new Jobs\BasicJob);
        Queue::push(new Jobs\BasicJob);

        $this->databaseQueue()->pop('default');

        $this->assertSame(1, $this->databaseQueue()->readyNow('default'));
    }

    public function test_ready_now_returns_zero_for_empty_queue()
    {
        $this->assertSame(0, $this->databaseQueue()->readyNow('default'));
    }

    public function test_wait_time_calculator_integrates_with_database_queue()
    {
        Queue::push(new Jobs\BasicJob);
        Queue::push(new Jobs\BasicJob);
        Queue::push(new Jobs\BasicJob);

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('runtimeForQueue')->with('default')->andReturn(1000.0);
        $this->app->instance(MetricsRepository::class, $metrics);

        $supervisors = Mockery::mock(SupervisorRepository::class);
        $supervisors->shouldReceive('all')->andReturn([
            (object) ['processes' => ['database:default' => 1]],
        ]);
        $this->app->instance(SupervisorRepository::class, $supervisors);

        $calculator = new WaitTimeCalculator(
            $this->app->make(QueueFactory::class),
            $supervisors,
            $metrics
        );

        $this->assertEquals(
            ['database:default' => 3],
            $calculator->calculate()
        );
    }

    public function test_workload_repository_returns_shape_for_database_queue()
    {
        Queue::push(new Jobs\BasicJob);
        Queue::push(new Jobs\BasicJob);

        $waitTime = Mockery::mock(WaitTimeCalculator::class);
        $waitTime->shouldReceive('calculate')->andReturn(['database:default' => 5]);
        $this->app->instance(WaitTimeCalculator::class, $waitTime);

        $supervisors = Mockery::mock(SupervisorRepository::class);
        $supervisors->shouldReceive('all')->andReturn([
            (object) ['processes' => ['database:default' => 3]],
        ]);
        $this->app->instance(SupervisorRepository::class, $supervisors);

        $workload = $this->app->make(WorkloadRepository::class)->get();

        $this->assertCount(1, $workload);
        $this->assertSame('default', $workload[0]['name']);
        $this->assertSame(2, $workload[0]['length']);
        $this->assertSame(5, $workload[0]['wait']);
        $this->assertSame(3, $workload[0]['processes']);
        $this->assertNull($workload[0]['split_queues']);
    }

    public function test_workload_repository_returns_split_queues_for_comma_separated_database_queues()
    {
        Queue::push(new Jobs\BasicJob, '', 'high');
        Queue::push(new Jobs\BasicJob, '', 'low');
        Queue::push(new Jobs\BasicJob, '', 'low');

        $waitTime = Mockery::mock(WaitTimeCalculator::class);
        $waitTime->shouldReceive('calculate')->andReturn(['database:high,low' => 7]);
        $waitTime->shouldReceive('calculateTimeToClear')
            ->with('database', 'high', 1)
            ->andReturn(3);
        $waitTime->shouldReceive('calculateTimeToClear')
            ->with('database', 'low', 1)
            ->andReturn(4);
        $this->app->instance(WaitTimeCalculator::class, $waitTime);

        $supervisors = Mockery::mock(SupervisorRepository::class);
        $supervisors->shouldReceive('all')->andReturn([
            (object) ['processes' => ['database:high,low' => 1]],
        ]);
        $this->app->instance(SupervisorRepository::class, $supervisors);

        $workload = $this->app->make(WorkloadRepository::class)->get();

        $this->assertCount(1, $workload);
        $this->assertSame(3, $workload[0]['length']);
        $this->assertCount(2, $workload[0]['split_queues']);
    }

    public function test_workload_is_empty_when_no_supervisors_are_active()
    {
        Queue::push(new Jobs\BasicJob);

        $supervisors = Mockery::mock(SupervisorRepository::class);
        $supervisors->shouldReceive('all')->andReturn([]);
        $this->app->instance(SupervisorRepository::class, $supervisors);

        $workload = $this->app->make(WorkloadRepository::class)->get();

        $this->assertSame([], $workload);
    }

    public function test_long_wait_detected_fires_for_database_queue()
    {
        config(['horizon.waits' => ['database:default' => 60]]);

        Event::fake();

        $calc = Mockery::mock(WaitTimeCalculator::class);
        $calc->shouldReceive('calculate')->andReturn([
            'database:default' => 120,
        ]);
        $this->app->instance(WaitTimeCalculator::class, $calc);

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('acquireWaitTimeMonitorLock')->andReturnTrue();
        $this->app->instance(MetricsRepository::class, $metrics);

        (new MonitorWaitTimes($metrics))->handle();

        Event::assertDispatched(LongWaitDetected::class, function ($event) {
            return $event->connection === 'database' && $event->queue === 'default';
        });
    }

    public function test_long_wait_can_be_silenced_by_setting_threshold_to_zero()
    {
        config(['horizon.waits' => ['database:default' => 0]]);

        Event::fake();

        $calc = Mockery::mock(WaitTimeCalculator::class);
        $calc->shouldReceive('calculate')->andReturn([
            'database:default' => 120,
        ]);
        $this->app->instance(WaitTimeCalculator::class, $calc);

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('acquireWaitTimeMonitorLock')->andReturnTrue();
        $this->app->instance(MetricsRepository::class, $metrics);

        (new MonitorWaitTimes($metrics))->handle();

        Event::assertNotDispatched(LongWaitDetected::class);
    }

    protected function databaseQueue(): DatabaseQueue
    {
        return Queue::connection('database');
    }
}
