<?php

namespace Laravel\Horizon\Tests\Unit;

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Laravel\Horizon\Dashboard\PendingJobCounts;
use Laravel\Horizon\Tests\IntegrationTest;
use Laravel\Horizon\WaitTimeCalculator;
use Mockery;
use RuntimeException;

class PendingJobCountsTest extends IntegrationTest
{
    public function test_it_aggregates_pending_state_across_unique_supervised_queues()
    {
        $waits = Mockery::mock(WaitTimeCalculator::class);
        $waits->shouldReceive('calculate')->never();

        $redisQueue = Mockery::mock();
        $redisQueue->shouldReceive('pendingState')->once()->with('default')->andReturn([
            'ready' => 3,
            'reserved' => 1,
            'delayed' => 2,
        ]);
        $redisQueue->shouldReceive('pendingState')->once()->with('reports')->andReturn([
            'ready' => 4,
            'reserved' => 0,
            'delayed' => 1,
        ]);

        $queues = Mockery::mock(QueueFactory::class);
        $queues->shouldReceive('connection')->once()->with('redis')->andReturn($redisQueue);

        $counts = (new PendingJobCounts($queues, $waits))->get([
            'redis:default' => 10,
            'redis:default,reports' => 20,
            'redis:reports' => 5,
        ]);

        $this->assertSame([
            'reserved' => 1,
            'ready' => 7,
            'delayed' => 3,
            'total' => 11,
        ], $counts);
    }

    public function test_it_returns_nulls_when_pending_state_is_unsupported()
    {
        $waits = Mockery::mock(WaitTimeCalculator::class);
        $waits->shouldReceive('calculate')->andReturn(['redis:default' => 1]);

        $customQueue = new class
        {
            // No pendingState() method.
        };

        $queues = Mockery::mock(QueueFactory::class);
        $queues->shouldReceive('connection')->once()->with('redis')->andReturn($customQueue);

        $counts = (new PendingJobCounts($queues, $waits))->get();

        $this->assertSame([
            'reserved' => null,
            'ready' => null,
            'delayed' => null,
            'total' => null,
        ], $counts);
    }

    public function test_it_returns_nulls_and_reports_when_pending_state_throws()
    {
        $waits = Mockery::mock(WaitTimeCalculator::class);

        $redisQueue = Mockery::mock();
        $redisQueue->shouldReceive('pendingState')
            ->once()
            ->with('default')
            ->andThrow(new RuntimeException('redis unavailable'));

        $queues = Mockery::mock(QueueFactory::class);
        $queues->shouldReceive('connection')->once()->with('redis')->andReturn($redisQueue);

        $counts = (new PendingJobCounts($queues, $waits))->get([
            'redis:default' => 1,
        ]);

        $this->assertSame([
            'reserved' => null,
            'ready' => null,
            'delayed' => null,
            'total' => null,
        ], $counts);
    }

    public function test_it_returns_nulls_when_pending_state_is_malformed()
    {
        $waits = Mockery::mock(WaitTimeCalculator::class);

        $redisQueue = Mockery::mock();
        $redisQueue->shouldReceive('pendingState')
            ->once()
            ->with('default')
            ->andReturn([
                'ready' => 2,
                'reserved' => 'busy',
                // delayed intentionally omitted
            ]);

        $queues = Mockery::mock(QueueFactory::class);
        $queues->shouldReceive('connection')->once()->with('redis')->andReturn($redisQueue);

        $counts = (new PendingJobCounts($queues, $waits))->get([
            'redis:default' => 1,
        ]);

        $this->assertSame([
            'reserved' => null,
            'ready' => null,
            'delayed' => null,
            'total' => null,
        ], $counts);
    }
}
