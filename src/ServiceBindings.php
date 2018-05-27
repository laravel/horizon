<?php

namespace Laravel\Horizon;

trait ServiceBindings
{
    /**
     * All of the service bindings for Horizon.
     *
     * @var array
     */
    public $bindings = [
        // General services...
        AutoScaler::class,
        Contracts\HorizonCommandQueue::class => RedisHorizonCommandQueue::class,
        Listeners\TrimRecentJobs::class,
        Listeners\TrimFailedJobs::class,
        Lock::class,
        Stopwatch::class,

        // Repository services...
        Contracts\JobRepository::class => Repositories\RedisJobRepository::class,
        Contracts\MasterSupervisorRepository::class => Repositories\RedisMasterSupervisorRepository::class,
        Contracts\MetricsRepository::class => Repositories\RedisMetricsRepository::class,
        Contracts\ProcessRepository::class => Repositories\RedisProcessRepository::class,
        Contracts\SupervisorRepository::class => Repositories\RedisSupervisorRepository::class,
        Contracts\TagRepository::class => Repositories\RedisTagRepository::class,
        Contracts\WorkloadRepository::class => Repositories\RedisWorkloadRepository::class,
    ];
}
