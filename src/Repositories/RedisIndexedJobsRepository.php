<?php

declare(strict_types=1);

namespace Laravel\Horizon\Repositories;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\IndexedJobsRepository;

class RedisIndexedJobsRepository implements IndexedJobsRepository
{
    /**
     * The Redis connection instance.
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    public $redis;

    /**
     * The job repository implementation.
     *
     * @var RedisJobRepository
     */
    public $redisJobRepository;

    public function __construct(RedisFactory $redis, RedisJobRepository $redisJobRepository)
    {
        $this->redis = $redis;
        $this->redisJobRepository = $redisJobRepository;
    }

    public function getKeysByJobNameAndStatus(string $jobName, string $status): array
    {
        $jobs = $this->connection()->pipeline(function ($pipe) use ($jobName, $status) {
            $prefix = $status . '_jobs';
            $indexPrefix = config('horizon.prefix_index', 'index');
            $pipe->keys("{$prefix}:{$indexPrefix}{$jobName}");
        });

        return $jobs[0];
    }

    /**
     * @param $startingAt
     * @param string $jobName
     * @param string $createdAtFrom
     * @param string $createdAtTo
     * @return \Illuminate\Support\Collection
     */
    public function getIndexedPending($startingAt, $jobName = null, $createdAtFrom = null, $createdAtTo = null)
    {
        $from = !empty($createdAtFrom) ? strtotime($createdAtFrom) : '-inf';
        $to = !empty($createdAtTo) ? strtotime($createdAtTo) : '+inf';

        $key = empty($jobName) ? 'pending_jobs' : $this->getKeysByJobNameAndStatus($jobName, 'pending')[0];
        $indexPrefix = config('horizon.prefix');
        $key = Str::after($key, "{$indexPrefix}");

        $options = [
            'limit' => [
                'offset' => $startingAt + 1,
                'count' => $startingAt + 50,
            ]
        ];

        return $this->redisJobRepository->getJobs($this->connection()->zrangebyscore(
            $key, $from, $to, $options
        ), $startingAt + 1);
    }

    public function getIndexedCompleted(string $jobName): array
    {
        return [];
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
