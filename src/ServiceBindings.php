<?php

namespace Laravel\Horizon;

use Laravel\Horizon\Exceptions\UnsupportedDriverException;

trait ServiceBindings
{
    /**
     * The driver-neutral service bindings for Horizon.
     *
     * @var array
     */
    public $serviceBindings = [
        // General services...
        AutoScaler::class,
        Listeners\TrimRecentJobs::class,
        Listeners\TrimFailedJobs::class,
        Listeners\TrimMonitoredJobs::class,
        Stopwatch::class,

        // Notifications...
        Contracts\LongWaitDetectedNotification::class => Notifications\LongWaitDetected::class,
    ];

    /**
     * The driver-specific service bindings for Horizon.
     *
     * @var array<string, array<class-string|int, class-string>>
     */
    public $driverServiceBindings = [
        'redis' => [
            Lock::class => RedisLock::class,
            Contracts\HorizonCommandQueue::class => RedisHorizonCommandQueue::class,
            Contracts\JobRepository::class => Repositories\RedisJobRepository::class,
            Contracts\MasterSupervisorRepository::class => Repositories\RedisMasterSupervisorRepository::class,
            Contracts\MetricsRepository::class => Repositories\RedisMetricsRepository::class,
            Contracts\ProcessRepository::class => Repositories\RedisProcessRepository::class,
            Contracts\SupervisorRepository::class => Repositories\RedisSupervisorRepository::class,
            Contracts\TagRepository::class => Repositories\RedisTagRepository::class,
            Contracts\WorkloadRepository::class => Repositories\QueueWorkloadRepository::class,
        ],

        'database' => [
            Lock::class => DatabaseLock::class,
            Contracts\HorizonCommandQueue::class => DatabaseHorizonCommandQueue::class,
            Contracts\JobRepository::class => Repositories\DatabaseJobRepository::class,
            Contracts\MasterSupervisorRepository::class => Repositories\DatabaseMasterSupervisorRepository::class,
            Contracts\MetricsRepository::class => Repositories\DatabaseMetricsRepository::class,
            Contracts\ProcessRepository::class => Repositories\DatabaseProcessRepository::class,
            Contracts\SupervisorRepository::class => Repositories\DatabaseSupervisorRepository::class,
            Contracts\TagRepository::class => Repositories\DatabaseTagRepository::class,
            Contracts\WorkloadRepository::class => Repositories\QueueWorkloadRepository::class,
        ],
    ];

    /**
     * Resolve the full set of service bindings for the given Horizon driver.
     *
     * @param  string  $driver
     * @return array
     *
     * @throws \Laravel\Horizon\Exceptions\UnsupportedDriverException
     */
    public function serviceBindingsFor($driver)
    {
        if (! isset($this->driverServiceBindings[$driver])) {
            throw new UnsupportedDriverException(
                "Horizon does not support the [{$driver}] driver."
            );
        }

        return array_merge($this->serviceBindings, $this->driverServiceBindings[$driver]);
    }
}
