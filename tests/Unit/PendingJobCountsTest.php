<?php

namespace Laravel\Horizon\Tests\Unit;

use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Laravel\Horizon\Dashboard\PendingJobCounts;
use Laravel\Horizon\Tests\UnitTest;
use Laravel\Horizon\WaitTimeCalculator;
use Mockery;
use RuntimeException;

class PendingJobCountsTest extends UnitTest
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

        $customQueue = new class {
            // No pendingState() method.
        };

        $queues = Mockery::mock(QueueFactory::class);
        $queues->shouldReceive('connection')->once()->with('redis')->andReturn($customQueue);

        $this->withSilentExceptionHandler();

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

        $this->withSilentExceptionHandler();

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

        $this->withSilentExceptionHandler();

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

    /**
     * Unit tests do not boot the application; bind a silent handler so report() works.
     */
    private function withSilentExceptionHandler(): void
    {
        $container = new Container;
        Container::setInstance($container);

        $handler = Mockery::mock(ExceptionHandler::class);
        $handler->shouldReceive('report')->andReturnNull();
        $handler->shouldReceive('shouldReport')->andReturn(false);
        $handler->shouldReceive('render')->zeroOrMoreTimes();
        $handler->shouldReceive('renderForConsole')->zeroOrMoreTimes();

        $container->instance(ExceptionHandler::class, $handler);
        $container->instance('app', $container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        parent::tearDown();
    }
}
