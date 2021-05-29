<?php

declare(strict_types=1);

namespace Laravel\Horizon\Repositories;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Laravel\Horizon\Contracts\IndexedJobsRepository;

class RedisIndexedJobsRepository implements IndexedJobsRepository
{
    /**
     * The Redis connection instance.
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    public $redis;

    public function __construct(RedisFactory $redis)
    {
        $this->redis = $redis;
    }

    public function getKeysByJobNameAndStatus(string $jobName, string $status): array
    {
        $jobs = $this->connection()->pipeline(function ($pipe) use ($jobName, $status) {
            $prefix = $status . '_jobs';
            $indexPrefix = config('horizon.prefix_index', 'index');
            $pipe->keys("{$prefix}:{$indexPrefix}:*{$jobName}*");
        });

        return array_shift($jobs);
    }

    /**
     * Get the Redis connection instance.
     *
     * @return \Illuminate\Redis\Connections\Connection
     */
    protected function connection()
    {
        return $this->redis->connection('horizon');
    }
}
