<?php

namespace Laravel\Horizon\Tests\Unit;

use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Queue\QueueManager;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;
use Laravel\Horizon\Dashboard\Workload;
use Laravel\Horizon\Queues\QueuePauseMetadata;
use Laravel\Horizon\Queues\QueuePauseStatus;
use Laravel\Horizon\Support\FrameworkCapabilities;
use Laravel\Horizon\Tests\UnitTest;
use Mockery;
use RuntimeException;
use Throwable;

class WorkloadTest extends UnitTest
{
    public function test_legacy_custom_rows_without_connection_get_safe_pause_state()
    {
        $repository = Mockery::mock(WorkloadRepository::class);
        $repository->shouldReceive('get')->once()->andReturn([
            [
                'name' => 'default',
                'length' => 3,
                'wait' => 1,
                'processes' => 2,
                'split_queues' => null,
            ],
        ]);

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('throughputForQueue')->once()->with('default')->andReturn(7);

        $workload = (new Workload($repository, $this->pauseStatusNeverCalled(), $metrics))->get();

        $this->assertSame([
            [
                'name' => 'default',
                'length' => 3,
                'wait' => 1,
                'processes' => 2,
                'split_queues' => null,
                'connection' => null,
                'paused' => false,
                'pausedUntil' => null,
                'throughput' => 7,
            ],
        ], $workload);
    }

    public function test_empty_and_invalid_connections_are_treated_as_unavailable()
    {
        $repository = Mockery::mock(WorkloadRepository::class);
        $repository->shouldReceive('get')->once()->andReturn([
            [
                'name' => 'empty-connection',
                'connection' => '',
                'length' => 1,
                'wait' => 0,
                'processes' => 1,
                'split_queues' => null,
            ],
            [
                'name' => 'numeric-connection',
                'connection' => 0,
                'length' => 1,
                'wait' => 0,
                'processes' => 1,
                'split_queues' => null,
            ],
        ]);

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('throughputForQueue')->once()->with('empty-connection')->andReturn(1);
        $metrics->shouldReceive('throughputForQueue')->once()->with('numeric-connection')->andReturn(2);

        $workload = (new Workload($repository, $this->pauseStatusNeverCalled(), $metrics))->get();

        $this->assertCount(2, $workload);
        $this->assertNull($workload[0]['connection']);
        $this->assertFalse($workload[0]['paused']);
        $this->assertNull($workload[0]['pausedUntil']);
        $this->assertSame(1, $workload[0]['throughput']);
        $this->assertNull($workload[1]['connection']);
        $this->assertFalse($workload[1]['paused']);
        $this->assertNull($workload[1]['pausedUntil']);
        $this->assertSame(2, $workload[1]['throughput']);
    }

    public function test_split_queues_without_connection_receive_safe_pause_state()
    {
        $repository = Mockery::mock(WorkloadRepository::class);
        $repository->shouldReceive('get')->once()->andReturn([
            [
                'name' => 'default,high',
                'length' => 5,
                'wait' => 4,
                'processes' => 3,
                'split_queues' => [
                    ['name' => 'default', 'length' => 2, 'wait' => 1],
                    ['name' => 'high', 'length' => 3, 'wait' => 4],
                ],
            ],
        ]);

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('throughputForQueue')->once()->with('default')->andReturn(4);
        $metrics->shouldReceive('throughputForQueue')->once()->with('high')->andReturn(6);
        $metrics->shouldNotReceive('throughputForQueue')->with('default,high');

        $workload = (new Workload($repository, $this->pauseStatusNeverCalled(), $metrics))->get();

        $this->assertNull($workload[0]['connection']);
        $this->assertFalse($workload[0]['paused']);
        $this->assertNull($workload[0]['pausedUntil']);
        $this->assertSame(10, $workload[0]['throughput']);
        $this->assertSame([
            [
                'name' => 'default',
                'length' => 2,
                'wait' => 1,
                'paused' => false,
                'pausedUntil' => null,
                'throughput' => 4,
            ],
            [
                'name' => 'high',
                'length' => 3,
                'wait' => 4,
                'paused' => false,
                'pausedUntil' => null,
                'throughput' => 6,
            ],
        ], $workload[0]['split_queues']);
    }

    public function test_valid_connection_rows_are_enriched_with_pause_status()
    {
        $repository = Mockery::mock(WorkloadRepository::class);
        $repository->shouldReceive('get')->once()->andReturn([
            [
                'name' => 'reports',
                'connection' => 'redis',
                'length' => 4,
                'wait' => 2,
                'processes' => 1,
                'split_queues' => null,
            ],
            [
                'name' => 'default,high',
                'connection' => 'redis',
                'length' => 6,
                'wait' => 5,
                'processes' => 2,
                'split_queues' => [
                    ['name' => 'default', 'length' => 1, 'wait' => 1],
                    ['name' => 'high', 'length' => 5, 'wait' => 5],
                ],
            ],
        ]);

        $queues = Mockery::mock(QueueManager::class);
        // Workload rows are sorted by name: "default,high" then "reports".
        $queues->shouldReceive('isPaused')->once()->with('redis', 'default')->andReturn(false);
        $queues->shouldReceive('isPaused')->once()->with('redis', 'high')->andReturn(true);
        $queues->shouldReceive('isPaused')->once()->with('redis', 'reports')->andReturn(true);

        $defaultKey = 'horizon:queue-pause:'.hash('sha256', "redis\0default");
        $highKey = 'horizon:queue-pause:'.hash('sha256', "redis\0high");
        $reportsKey = 'horizon:queue-pause:'.hash('sha256', "redis\0reports");

        $store = Mockery::mock(CacheRepository::class);
        $store->shouldReceive('forget')->once()->with($defaultKey);
        $store->shouldReceive('get')->once()->with($highKey)->andReturn(null);
        $store->shouldReceive('get')->once()->with($reportsKey)->andReturn(1_700_000_000);

        $cache = Mockery::mock(CacheFactory::class);
        $cache->shouldReceive('store')->andReturn($store);

        $pauseStatus = new QueuePauseStatus(
            $queues,
            new QueuePauseMetadata($cache),
            new FrameworkCapabilities(queuePausing: true, queuePauseFor: true),
        );

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('throughputForQueue')->once()->with('default')->andReturn(3);
        $metrics->shouldReceive('throughputForQueue')->once()->with('high')->andReturn(8);
        $metrics->shouldReceive('throughputForQueue')->once()->with('reports')->andReturn(12);
        $metrics->shouldNotReceive('throughputForQueue')->with('default,high');

        $workload = (new Workload($repository, $pauseStatus, $metrics))->get();

        $this->assertSame('redis', $workload[0]['connection']);
        $this->assertSame('default,high', $workload[0]['name']);
        $this->assertFalse($workload[0]['paused']);
        $this->assertNull($workload[0]['pausedUntil']);
        $this->assertSame(11, $workload[0]['throughput']);
        $this->assertSame([
            [
                'name' => 'default',
                'length' => 1,
                'wait' => 1,
                'paused' => false,
                'pausedUntil' => null,
                'throughput' => 3,
            ],
            [
                'name' => 'high',
                'length' => 5,
                'wait' => 5,
                'paused' => true,
                'pausedUntil' => null,
                'throughput' => 8,
            ],
        ], $workload[0]['split_queues']);

        $this->assertSame('redis', $workload[1]['connection']);
        $this->assertSame('reports', $workload[1]['name']);
        $this->assertTrue($workload[1]['paused']);
        $this->assertSame(1_700_000_000, $workload[1]['pausedUntil']);
        $this->assertSame(12, $workload[1]['throughput']);
    }

    public function test_parent_throughput_is_null_when_any_child_throughput_is_unavailable()
    {
        $repository = Mockery::mock(WorkloadRepository::class);
        $repository->shouldReceive('get')->once()->andReturn([
            [
                'name' => 'default,high',
                'connection' => 'redis',
                'length' => 5,
                'wait' => 2,
                'processes' => 2,
                'split_queues' => [
                    ['name' => 'default', 'length' => 2, 'wait' => 1],
                    ['name' => 'high', 'length' => 3, 'wait' => 2],
                ],
            ],
        ]);

        $queues = Mockery::mock(QueueManager::class);
        $queues->shouldReceive('isPaused')->andReturn(false);

        $store = Mockery::mock(CacheRepository::class);
        $store->shouldReceive('forget')->twice();

        $cache = Mockery::mock(CacheFactory::class);
        $cache->shouldReceive('store')->andReturn($store);

        $pauseStatus = new QueuePauseStatus(
            $queues,
            new QueuePauseMetadata($cache),
            new FrameworkCapabilities(queuePausing: true, queuePauseFor: true),
        );

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('throughputForQueue')->once()->with('default')->andReturn(5);
        $metrics->shouldReceive('throughputForQueue')->once()->with('high')->andThrow(new RuntimeException('metrics unavailable'));

        $this->withSilentExceptionHandler();

        $workload = (new Workload($repository, $pauseStatus, $metrics))->get();

        $this->assertNull($workload[0]['throughput']);
        $this->assertSame(5, $workload[0]['split_queues'][0]['throughput']);
        $this->assertNull($workload[0]['split_queues'][1]['throughput']);
    }

    public function test_metric_lookup_failures_return_null_throughput_for_single_queues()
    {
        $repository = Mockery::mock(WorkloadRepository::class);
        $repository->shouldReceive('get')->once()->andReturn([
            [
                'name' => 'reports',
                'connection' => 'redis',
                'length' => 1,
                'wait' => 0,
                'processes' => 1,
                'split_queues' => null,
            ],
        ]);

        $queues = Mockery::mock(QueueManager::class);
        $queues->shouldReceive('isPaused')->once()->with('redis', 'reports')->andReturn(false);

        $store = Mockery::mock(CacheRepository::class);
        $store->shouldReceive('forget')->once();

        $cache = Mockery::mock(CacheFactory::class);
        $cache->shouldReceive('store')->andReturn($store);

        $pauseStatus = new QueuePauseStatus(
            $queues,
            new QueuePauseMetadata($cache),
            new FrameworkCapabilities(queuePausing: true, queuePauseFor: true),
        );

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('throughputForQueue')->once()->with('reports')->andThrow(new RuntimeException('boom'));

        $this->withSilentExceptionHandler();

        $workload = (new Workload($repository, $pauseStatus, $metrics))->get();

        $this->assertNull($workload[0]['throughput']);
        $this->assertSame('reports', $workload[0]['name']);
        $this->assertFalse($workload[0]['paused']);
    }

    public function test_non_numeric_throughput_is_treated_as_unavailable()
    {
        $repository = Mockery::mock(WorkloadRepository::class);
        $repository->shouldReceive('get')->once()->andReturn([
            [
                'name' => 'reports',
                'connection' => 'redis',
                'length' => 1,
                'wait' => 0,
                'processes' => 1,
                'split_queues' => null,
            ],
            [
                'name' => 'exports',
                'connection' => 'redis',
                'length' => 1,
                'wait' => 0,
                'processes' => 1,
                'split_queues' => null,
            ],
            [
                'name' => 'idle',
                'connection' => 'redis',
                'length' => 0,
                'wait' => 0,
                'processes' => 1,
                'split_queues' => null,
            ],
        ]);

        $queues = Mockery::mock(QueueManager::class);
        $queues->shouldReceive('isPaused')->andReturn(false);

        $store = Mockery::mock(CacheRepository::class);
        $store->shouldReceive('forget')->times(3);

        $cache = Mockery::mock(CacheFactory::class);
        $cache->shouldReceive('store')->andReturn($store);

        $pauseStatus = new QueuePauseStatus(
            $queues,
            new QueuePauseMetadata($cache),
            new FrameworkCapabilities(queuePausing: true, queuePauseFor: true),
        );

        $metrics = Mockery::mock(MetricsRepository::class);
        // Sorted by name: exports, idle, reports.
        $metrics->shouldReceive('throughputForQueue')->once()->with('exports')->andReturn('n/a');
        $metrics->shouldReceive('throughputForQueue')->once()->with('idle')->andReturn(0);
        $metrics->shouldReceive('throughputForQueue')->once()->with('reports')->andReturn(null);

        $workload = (new Workload($repository, $pauseStatus, $metrics))->get();

        $this->assertSame('exports', $workload[0]['name']);
        $this->assertNull($workload[0]['throughput']);
        $this->assertSame('idle', $workload[1]['name']);
        $this->assertSame(0, $workload[1]['throughput']);
        $this->assertSame('reports', $workload[2]['name']);
        $this->assertNull($workload[2]['throughput']);
    }

    public function test_empty_split_queues_parent_throughput_is_null()
    {
        $repository = Mockery::mock(WorkloadRepository::class);
        $repository->shouldReceive('get')->once()->andReturn([
            [
                'name' => 'grouped',
                'connection' => 'redis',
                'length' => 0,
                'wait' => 0,
                'processes' => 1,
                'split_queues' => [],
            ],
        ]);

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldNotReceive('throughputForQueue');

        $workload = (new Workload($repository, $this->pauseStatusNeverCalled(), $metrics))->get();

        $this->assertSame([], $workload[0]['split_queues']);
        $this->assertNull($workload[0]['throughput']);
        $this->assertFalse($workload[0]['paused']);
        $this->assertNull($workload[0]['pausedUntil']);
    }

    /**
     * QueuePauseStatus is final; use a real instance that must not be consulted.
     */
    private function pauseStatusNeverCalled(): QueuePauseStatus
    {
        $queues = Mockery::mock(QueueManager::class);
        $queues->shouldNotReceive('isPaused');

        $store = Mockery::mock(CacheRepository::class);
        $store->shouldNotReceive('get');
        $store->shouldNotReceive('forget');
        $store->shouldNotReceive('put');

        $cache = Mockery::mock(CacheFactory::class);
        $cache->shouldReceive('store')->never();

        return new QueuePauseStatus(
            $queues,
            new QueuePauseMetadata($cache),
            new FrameworkCapabilities(queuePausing: true, queuePauseFor: true),
        );
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
