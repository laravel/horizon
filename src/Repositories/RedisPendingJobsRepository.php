<?php

namespace Laravel\Horizon\Repositories;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;
use Laravel\Horizon\Contracts\PendingJobsRepository;

class RedisPendingJobsRepository implements PendingJobsRepository
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

    /**
     * Delete the jobs with the given IDs
     *
     * @param array $ids
     * @return void
     */
    public function deleteByIds(array $ids): void
    {
        $jobs = $this->connection()->pipeline(function ($pipe) use ($ids) {
            foreach ($ids as $id) {
                $pipe->hmget($id, ['id', 'status']);
            }
        });

        $ids = collect($jobs)
            ->filter(function (array $job) {
                $job = is_array($job) ? array_values($job) : null;

                $hasId = is_array($job) && $job[0] !== null && $job[0] !== false;
                $hasPending = isset($job[1]) && $job[1] === 'pending';

                return $hasId && $hasPending;
            })
            ->map(function ($job) {
                return array_values($job)[0];
            })
            ->toArray();

        $this->deleteJobs($ids);
    }

    /**
     * Delete the job with the given ID
     *
     * @param array $ids
     * @return void
     */
    private function deleteJobs(array $ids): void
    {
        $this->connection()->pipeline(function ($pipe) use ($ids) {

            $pipe->del($ids);

            $pipe->zrem('pending_jobs', ...$ids);
            $pipe->zrem('recent_jobs', ...$ids);
        });
    }

    /**
     * Get the Redis connection instance.
     *
     * @return \Illuminate\Redis\Connections\Connection
     */
    protected function connection(): Connection
    {
        return $this->redis->connection('horizon');
    }
}
