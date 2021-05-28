<?php

namespace Laravel\Horizon\Repositories;

use Illuminate\Contracts\Redis\Factory as RedisFactory;

class RedisStatisticsRepository
{
    /**
     * The Redis connection instance.
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    public $redis;

    /**
     * Create a new repository instance.
     *
     * @param \Illuminate\Contracts\Redis\Factory $redis
     * @return void
     */
    public function __construct(RedisFactory $redis)
    {
        $this->redis = $redis;
    }

    /**
     * @param string $type
     * @return array
     */
    public function statisticsByType(string $type): array
    {
        if (!in_array($type, ['pending_jobs', 'completed_jobs', 'failed_jobs'])) {
            throw new \Exception("Invalid type: $type");
        }

        $keys = $this->connection()->keys("$type:index*");

        return collect($keys)
            ->map(function ($key) {

                $class = preg_match('/index:(.*)$/', $key, $matches) ? $matches[1] : $key;

                $keyForCount = preg_match('/laravel_horizon:(.*)$/', $key, $matches) ? $matches[1] : $key;

                return [
                    'class' => $class,
                    'count' => $this->connection()->zcount($keyForCount, '-inf', '+inf'),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->toArray();
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
