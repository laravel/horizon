<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\Contracts\HorizonCommandQueue;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\ProcessRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;
use Laravel\Horizon\Exceptions\UnsupportedDriverException;
use Laravel\Horizon\HorizonServiceProvider;
use Laravel\Horizon\RedisHorizonCommandQueue;
use Laravel\Horizon\RedisQueue;
use Laravel\Horizon\Repositories\QueueWorkloadRepository;
use Laravel\Horizon\Repositories\RedisJobRepository;
use Laravel\Horizon\Repositories\RedisMasterSupervisorRepository;
use Laravel\Horizon\Repositories\RedisMetricsRepository;
use Laravel\Horizon\Repositories\RedisProcessRepository;
use Laravel\Horizon\Repositories\RedisSupervisorRepository;
use Laravel\Horizon\Repositories\RedisTagRepository;
use Laravel\Horizon\Repositories\RedisWorkloadRepository;
use Laravel\Horizon\Tests\IntegrationTest;

class DriverConfigurationTest extends IntegrationTest
{
    public function test_horizon_driver_defaults_to_redis()
    {
        $this->assertSame('redis', config('horizon.driver'));
    }

    public function test_redis_driver_binds_redis_repositories()
    {
        $this->assertInstanceOf(RedisJobRepository::class, $this->app->make(JobRepository::class));
        $this->assertInstanceOf(RedisMasterSupervisorRepository::class, $this->app->make(MasterSupervisorRepository::class));
        $this->assertInstanceOf(RedisMetricsRepository::class, $this->app->make(MetricsRepository::class));
        $this->assertInstanceOf(RedisProcessRepository::class, $this->app->make(ProcessRepository::class));
        $this->assertInstanceOf(RedisSupervisorRepository::class, $this->app->make(SupervisorRepository::class));
        $this->assertInstanceOf(RedisTagRepository::class, $this->app->make(TagRepository::class));
        $this->assertInstanceOf(QueueWorkloadRepository::class, $this->app->make(WorkloadRepository::class));
        $this->assertInstanceOf(RedisHorizonCommandQueue::class, $this->app->make(HorizonCommandQueue::class));
    }

    public function test_redis_queue_connector_wraps_queues_with_horizon_redis_queue()
    {
        $queue = $this->app->make('queue')->connection('redis');

        $this->assertInstanceOf(RedisQueue::class, $queue);
    }

    public function test_redis_workload_repository_alias_resolves_to_queue_workload_repository()
    {
        $this->assertTrue(class_exists(RedisWorkloadRepository::class));

        $this->assertInstanceOf(
            QueueWorkloadRepository::class,
            $this->app->make(RedisWorkloadRepository::class)
        );
    }

    public function test_unsupported_driver_throws_exception()
    {
        $provider = new HorizonServiceProvider($this->app);

        $this->expectException(UnsupportedDriverException::class);
        $this->expectExceptionMessage('Horizon does not support the [foo] driver.');

        $provider->serviceBindingsFor('foo');
    }

    public function test_service_bindings_for_returns_expected_redis_bindings()
    {
        $provider = new HorizonServiceProvider($this->app);

        $bindings = $provider->serviceBindingsFor('redis');

        $this->assertSame(RedisJobRepository::class, $bindings[JobRepository::class]);
        $this->assertSame(RedisMasterSupervisorRepository::class, $bindings[MasterSupervisorRepository::class]);
        $this->assertSame(RedisMetricsRepository::class, $bindings[MetricsRepository::class]);
        $this->assertSame(RedisProcessRepository::class, $bindings[ProcessRepository::class]);
        $this->assertSame(RedisSupervisorRepository::class, $bindings[SupervisorRepository::class]);
        $this->assertSame(RedisTagRepository::class, $bindings[TagRepository::class]);
        $this->assertSame(QueueWorkloadRepository::class, $bindings[WorkloadRepository::class]);
        $this->assertSame(RedisHorizonCommandQueue::class, $bindings[HorizonCommandQueue::class]);
    }
}
