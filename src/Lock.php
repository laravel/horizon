<?php

namespace Laravel\Horizon;

use Illuminate\Contracts\Redis\Factory as RedisFactory;

class Lock
{
    /**
     * The Redis factory implementation.
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    public $redis;

    /**
     * Create a Horizon lock manager.
     *
     * @param  \Illuminate\Contracts\Redis\Factory  $redis
     * @return void
     */
    public function __construct(RedisFactory $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Execute the given callback if a lock can be acquired.
     *
     * @param  string  $key
     * @param  \Closure  $callback
     * @param  int  $seconds
     * @return void
     */
    public function with($key, $callback, $seconds = 60)
    {
        if ($this->get($key, $seconds)) {
            try {
                call_user_func($callback);
            } finally {
                $this->release($key);
            }
        }
    }

    /**
     * Determine if a lock exists for the given key.
     *
     * @param  string  $key
     * @return bool
     */
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
    public function get($key, $seconds = 60)
    {
        return $this->connection()->set($key, 1, 'EX', $seconds, 'NX') === true;
    }

    /**
     * Release the lock for the given key.
     *
     * @param  string  $key
     * @return void
     */
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
