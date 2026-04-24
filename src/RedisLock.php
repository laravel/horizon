<?php

namespace Laravel\Horizon;

use Illuminate\Contracts\Redis\Factory as RedisFactory;

class RedisLock extends Lock
{
    /**
     * The Redis factory implementation.
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    public $redis;

    /**
     * Create a Horizon Redis lock manager.
     */
    public function __construct(RedisFactory $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Determine if a lock exists for the given key.
     *
     * @param  string  $key
     * @return bool
     */
    #[\Override]
    public function exists($key)
    {
        return $this->connection()->exists($key) === 1;
    }

    /**
     * Attempt to get a lock for the given key.
     *
     * @param  string  $key
     * @param  int  $seconds
     * @return bool
     */
    #[\Override]
    public function get($key, $seconds = 60)
    {
        $result = $this->connection()->setnx($key, 1);

        if ($result === 1) {
            $this->connection()->expire($key, $seconds);
        }

        return $result === 1;
    }

    /**
     * Release the lock for the given key.
     *
     * @param  string  $key
     * @return void
     */
    #[\Override]
    public function release($key)
    {
        $this->connection()->del($key);
    }

    /**
     * Get the Redis connection instance.
     *
     * @return \Illuminate\Redis\Connections\Connection
     */
    public function connection()
    {
        return $this->redis->connection('horizon');
    }
}
