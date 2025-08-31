<?php

namespace Laravel\Horizon;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Laravel\Horizon\Contracts\HorizonCommandQueue;

class RedisHorizonCommandQueue implements HorizonCommandQueue
{
    /**
     * The Redis connection instance.
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    public $redis;

    /**
     * Create a new command queue instance.
     *
     * @param  \Illuminate\Contracts\Redis\Factory  $redis
     * @return void
     */
    public function __construct(RedisFactory $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Push a command onto a given queue.
     *
     * @param  string  $name
     * @param  string  $command
     * @param  array  $options
     * @return void
     */
    public function push($name, $command, array $options = [])
    {
        $this->connection()->rpush('commands:'.$name, json_encode([
            'command' => $command,
            'options' => $options,
        ]));
    }

    /**
     * Get the pending commands for a given queue name.
     *
     * @param  string  $name
     * @return array
     */
    public function pending($name)
    {
        $length = $this->connection()->llen('commands:'.$name);

        if ($length < 1) {
            return [];
        }

        $results = $this->connection()->pipeline(function ($pipe) use ($name, $length) {
            $pipe->lrange('commands:'.$name, 0, $length - 1);

            $pipe->ltrim('commands:'.$name, $length, -1);
        });

        return collect($results[0])
            ->map(fn ($result) => (object) json_decode($result, true))
            ->all();
    }

    /**
     * Flush the command queue for a given queue name.
     *
     * @param  string  $name
     * @return void
     */
    public function flush($name)
    {
        $this->connection()->del('commands:'.$name);
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
