<?php

namespace Laravel\Horizon\Tests\Unit;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Laravel\Horizon\Repositories\RedisMetricsRepository;
use Laravel\Horizon\Tests\UnitTest;
use Mockery;

class RedisMetricsRepositoryTest extends UnitTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container;
        $container->instance('config', new ConfigRepository([
            'horizon.prefix' => 'horizon:',
        ]));

        Container::setInstance($container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_clear_scans_prefixed_metric_patterns_by_default()
    {
        $repository = $this->redisMetricsRepositoryWithConnection(
            $this->connectionForClearingMetrics([
                'horizon:queue:*',
                'horizon:job:*',
                'horizon:snapshot:*',
            ])
        );

        $repository->clear();

        $this->assertTrue(true);
    }

    public function test_clear_uses_raw_metric_patterns_when_phpredis_scan_prefix_is_enabled()
    {
        $repository = $this->redisMetricsRepositoryWithConnection(
            $this->connectionForClearingMetrics([
                'queue:*',
                'job:*',
                'snapshot:*',
            ]),
            true
        );

        $repository->clear();

        $this->assertTrue(true);
    }

    public function test_phpredis_scan_prefix_option_is_detected()
    {
        if (! defined('Redis::SCAN_PREFIX')) {
            $this->markTestSkipped('The redis extension is required.');
        }

        $repository = $this->redisMetricsRepositoryDetectingScanPrefix(
            $this->connectionWithPhpRedisScanOption(\Redis::SCAN_PREFIX)
        );

        $this->assertTrue($repository->detectsPhpRedisScanPrefix());
    }

    public function test_other_phpredis_scan_options_are_not_detected_as_scan_prefix()
    {
        if (! defined('Redis::SCAN_PREFIX') || ! defined('Redis::SCAN_NOPREFIX')) {
            $this->markTestSkipped('The redis extension is required.');
        }

        $repository = $this->redisMetricsRepositoryDetectingScanPrefix(
            $this->connectionWithPhpRedisScanOption(\Redis::SCAN_NOPREFIX)
        );

        $this->assertFalse($repository->detectsPhpRedisScanPrefix());
    }

    protected function connectionForClearingMetrics(array $patterns)
    {
        $connection = Mockery::mock();

        foreach (['last_snapshot_at', 'measured_jobs', 'measured_queues', 'metrics:snapshot'] as $key) {
            $connection->shouldReceive('del')->once()->with($key);
        }

        foreach ($patterns as $pattern) {
            $connection->shouldReceive('scan')->once()->with(0, ['match' => $pattern])->andReturn([0, []]);
        }

        return $connection;
    }

    protected function connectionWithPhpRedisScanOption($option)
    {
        return new class($option)
        {
            public $option;

            public function __construct($option)
            {
                $this->option = $option;
            }

            public function client()
            {
                return new class($this->option)
                {
                    public $option;

                    public function __construct($option)
                    {
                        $this->option = $option;
                    }

                    public function getOption($option)
                    {
                        return $this->option;
                    }
                };
            }
        };
    }

    protected function redisMetricsRepositoryWithConnection($connection, $usesPhpRedisScanPrefix = false)
    {
        return new class(Mockery::mock(RedisFactory::class), $connection, $usesPhpRedisScanPrefix) extends RedisMetricsRepository
        {
            public $connection;

            public $usesPhpRedisScanPrefix;

            public function __construct($redis, $connection, $usesPhpRedisScanPrefix)
            {
                parent::__construct($redis);

                $this->connection = $connection;
                $this->usesPhpRedisScanPrefix = $usesPhpRedisScanPrefix;
            }

            public function connection()
            {
                return $this->connection;
            }

            protected function usesPhpRedisScanPrefix()
            {
                return $this->usesPhpRedisScanPrefix;
            }
        };
    }

    protected function redisMetricsRepositoryDetectingScanPrefix($connection)
    {
        return new class(Mockery::mock(RedisFactory::class), $connection) extends RedisMetricsRepository
        {
            public $connection;

            public function __construct($redis, $connection)
            {
                parent::__construct($redis);

                $this->connection = $connection;
            }

            public function connection()
            {
                return $this->connection;
            }

            public function detectsPhpRedisScanPrefix()
            {
                return $this->usesPhpRedisScanPrefix();
            }
        };
    }
}
