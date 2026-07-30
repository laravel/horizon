<?php

namespace Laravel\Horizon\Tests\Unit;

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Repositories\RedisWorkloadRepository;
use Laravel\Horizon\Tests\UnitTest;
use Laravel\Horizon\WaitTimeCalculator;
use Mockery;

class RedisWorkloadRepositoryTest extends UnitTest
{
    public function test_processing_is_false_when_no_reserved_jobs_exist()
    {
        $this->assertSame(false, $this->processingState(0));
    }

    public function test_processing_is_true_when_reserved_jobs_exist()
    {
        $this->assertSame(true, $this->processingState(1));
    }

    protected function processingState($reserved)
    {
        $queue = Mockery::mock(QueueFactory::class);
        $connection = Mockery::mock();
        $waitTime = Mockery::mock(WaitTimeCalculator::class);
        $masters = Mockery::mock(MasterSupervisorRepository::class);
        $supervisors = Mockery::mock(SupervisorRepository::class);
        $metrics = Mockery::mock(MetricsRepository::class);

        $supervisors->shouldReceive('all')->once()->andReturn([
            (object) [
                'processes' => [
                    'redis:default' => 4,
                ],
            ],
        ]);

        $queue->shouldReceive('connection')->with('redis')->andReturn($connection);

        $connection->shouldReceive('pendingState')->once()->with('default')->andReturn([
            'ready' => 100,
            'reserved' => $reserved,
            'delayed' => 50,
        ]);

        $waitTime->shouldNotReceive('calculateTimeToClear');
        $metrics->shouldNotReceive('throughputForQueue');

        $repository = new RedisWorkloadRepository(
            $queue,
            $waitTime,
            $masters,
            $supervisors,
            $metrics
        );

        return $repository->processing();
    }

    public function test_four_argument_construction_remains_valid_without_metrics()
    {
        $queue = Mockery::mock(QueueFactory::class);
        $connection = Mockery::mock();
        $waitTime = Mockery::mock(WaitTimeCalculator::class);
        $masters = Mockery::mock(MasterSupervisorRepository::class);
        $supervisors = Mockery::mock(SupervisorRepository::class);

        $supervisors->shouldReceive('all')->once()->andReturn([
            (object) [
                'processes' => [
                    'redis:default' => 2,
                ],
            ],
        ]);

        $queue->shouldReceive('connection')->with('redis')->andReturn($connection);

        $connection->shouldReceive('pendingState')->once()->with('default')->andReturn([
            'ready' => 7,
            'reserved' => 2,
            'delayed' => 1,
        ]);

        $waitTime->shouldReceive('calculateTimeToClear')
            ->once()
            ->with('redis', 'default', 2, ['default' => 7])
            ->andReturn(4);

        $repository = new RedisWorkloadRepository(
            $queue,
            $waitTime,
            $masters,
            $supervisors
        );

        $this->assertSame([
            [
                'connection' => 'redis',
                'name' => 'default',
                'length' => 7,
                'reserved' => 2,
                'delayed' => 1,
                'wait' => 4,
                'processes' => 2,
                'throughput' => 0,
                'split_queues' => null,
            ],
        ], $repository->get());
    }

    public function test_pending_state_is_aggregated_without_reading_ready_counts_twice()
    {
        $queue = Mockery::mock(QueueFactory::class);
        $connection = Mockery::mock();
        $waitTime = Mockery::mock(WaitTimeCalculator::class);
        $masters = Mockery::mock(MasterSupervisorRepository::class);
        $supervisors = Mockery::mock(SupervisorRepository::class);
        $metrics = Mockery::mock(MetricsRepository::class);

        $supervisors->shouldReceive('all')->once()->andReturn([
            (object) [
                'processes' => [
                    'redis:default' => 2,
                    'redis:reports,batches' => 3,
                ],
            ],
        ]);

        $queue->shouldReceive('connection')->with('redis')->andReturn($connection);

        $connection->shouldReceive('pendingState')->once()->with('default')->andReturn([
            'ready' => 7,
            'reserved' => 2,
            'delayed' => 1,
        ]);
        $connection->shouldReceive('pendingState')->once()->with('reports')->andReturn([
            'ready' => 5,
            'reserved' => 1,
            'delayed' => 0,
        ]);
        $connection->shouldReceive('pendingState')->once()->with('batches')->andReturn([
            'ready' => 9,
            'reserved' => 3,
            'delayed' => 4,
        ]);

        $waitTime->shouldReceive('calculateTimeToClear')
            ->once()
            ->with('redis', 'default', 2, ['default' => 7])
            ->andReturn(4);
        $waitTime->shouldReceive('calculateTimeToClear')
            ->once()
            ->with('redis', 'reports,batches', 3, ['reports' => 5, 'batches' => 9])
            ->andReturn(10);
        $waitTime->shouldReceive('calculateTimeToClear')
            ->once()
            ->with('redis', 'reports', 3, ['reports' => 5])
            ->andReturn(2);
        $waitTime->shouldReceive('calculateTimeToClear')
            ->once()
            ->with('redis', 'batches', 3, ['batches' => 9])
            ->andReturn(3);

        $metrics->shouldReceive('throughputForQueue')->once()->with('default')->andReturn(11);
        $metrics->shouldReceive('throughputForQueue')->once()->with('reports')->andReturn(4);
        $metrics->shouldReceive('throughputForQueue')->once()->with('batches')->andReturn(6);

        $repository = new RedisWorkloadRepository(
            $queue,
            $waitTime,
            $masters,
            $supervisors,
            $metrics
        );

        $this->assertSame([
            [
                'connection' => 'redis',
                'name' => 'default',
                'length' => 7,
                'reserved' => 2,
                'delayed' => 1,
                'wait' => 4,
                'processes' => 2,
                'throughput' => 11,
                'split_queues' => null,
            ],
            [
                'connection' => 'redis',
                'name' => 'reports,batches',
                'length' => 14,
                'reserved' => 4,
                'delayed' => 4,
                'wait' => 10,
                'processes' => 3,
                'throughput' => 10,
                'split_queues' => [
                    [
                        'connection' => 'redis',
                        'name' => 'reports',
                        'length' => 5,
                        'wait' => 2,
                        'throughput' => 4,
                    ],
                    [
                        'connection' => 'redis',
                        'name' => 'batches',
                        'length' => 9,
                        'wait' => 5,
                        'throughput' => 6,
                    ],
                ],
            ],
        ], $repository->get());
    }
}
