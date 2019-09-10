<?php

namespace Laravel\Horizon\Repositories;

use Cake\Chronos\Chronos;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Laravel\Horizon\Contracts\ProcessRepository;

class RedisProcessRepository implements ProcessRepository
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
     * @param  \Illuminate\Contracts\Redis\Factory  $redis
     * @return void
     */
    public function __construct(RedisFactory $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Get all of the orphan process IDs and the times they were observed.
     *
     * @param  string  $master
     * @return array
     */
    public function allOrphans($master)
    {
        return $this->connection()->hgetall(
            "{$master}:orphans"
        );
    }

    /**
     * Record the given process IDs as orphaned.
     *
     * @param  string  $master
     * @param  array  $processIds
     * @return void
     */
    public function orphaned($master, array $processIds)
    {
        $time = Chronos::now()->getTimestamp();

        $shouldRemove = array_diff($this->connection()->hkeys(
            $key = "{$master}:orphans"
        ), $processIds);

        if (! empty($shouldRemove)) {
            $this->connection()->hdel($key, ...$shouldRemove);
        }

        $this->connection()->pipeline(function ($pipe) use ($key, $time, $processIds) {
            foreach ($processIds as $processId) {
                $pipe->hsetnx($key, $processId, $time);
            }
        });
    }

    /**
     * Get the process IDs orphaned for at least the given number of seconds.
     *
     * @param  string  $master
     * @param  int  $seconds
     * @return array
     */
    public function orphanedFor($master, $seconds)
    {
        $expiresAt = Chronos::now()->getTimestamp() - $seconds;

        return collect($this->allOrphans($master))->filter(function ($recordedAt, $_) use ($expiresAt) {
            return $expiresAt > $recordedAt;
        })->keys()->all();
    }

    /**
     * Remove the given process IDs from the orphan list.
     *
     * @param  string  $master
     * @param  array  $processIds
     * @return void
     */
    public function forgetOrphans($master, array $processIds)
    {
        $this->connection()->hdel(
            "{$master}:orphans", ...$processIds
        );
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
