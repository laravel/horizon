<?php

namespace Laravel\Horizon;

trait ServiceBindings
{
    /**
     * The Redis-backed service bindings for Horizon.
     *
     * @var array
     */
    public $redisServiceBindings = [
        Lock::class => RedisLock::class,
        Contracts\HorizonCommandQueue::class => RedisHorizonCommandQueue::class,
        Contracts\JobRepository::class => Repositories\RedisJobRepository::class,
        Contracts\MasterSupervisorRepository::class => Repositories\RedisMasterSupervisorRepository::class,
        Contracts\MetricsRepository::class => Repositories\RedisMetricsRepository::class,
        Contracts\ProcessRepository::class => Repositories\RedisProcessRepository::class,
        Contracts\SupervisorRepository::class => Repositories\RedisSupervisorRepository::class,
        Contracts\TagRepository::class => Repositories\RedisTagRepository::class,
        Contracts\WorkloadRepository::class => Repositories\RedisWorkloadRepository::class,
    ];

    /**
     * The database-backed service bindings for Horizon.
     *
     * @var array
     */
    public $databaseServiceBindings = [
        Lock::class => DatabaseLock::class,
        Contracts\HorizonCommandQueue::class => DatabaseHorizonCommandQueue::class,
        Contracts\JobRepository::class => Repositories\DatabaseJobRepository::class,
        Contracts\MasterSupervisorRepository::class => Repositories\DatabaseMasterSupervisorRepository::class,
        Contracts\MetricsRepository::class => Repositories\DatabaseMetricsRepository::class,
        Contracts\ProcessRepository::class => Repositories\DatabaseProcessRepository::class,
        Contracts\SupervisorRepository::class => Repositories\DatabaseSupervisorRepository::class,
        Contracts\TagRepository::class => Repositories\DatabaseTagRepository::class,
        Contracts\WorkloadRepository::class => Repositories\DatabaseWorkloadRepository::class,
    ];

    /**
     * The driver-agnostic service bindings for Horizon.
     *
     * @var array
     */
    public $serviceBindings = [
        AutoScaler::class,
        Listeners\TrimRecentJobs::class,
        Listeners\TrimFailedJobs::class,
        Listeners\TrimMonitoredJobs::class,
        Stopwatch::class,

        // Notifications...
        Contracts\LongWaitDetectedNotification::class => Notifications\LongWaitDetected::class,
    ];
}
