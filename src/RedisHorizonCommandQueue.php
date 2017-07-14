<?php

namespace Laravel\Horizon;

use Laravel\Horizon\Contracts\HorizonCommandQueue;
use Illuminate\Contracts\Redis\Factory as RedisFactory;

class RedisHorizonCommandQueue implements HorizonCommandQueue
{
    /**
     * The Redis connection instance.
     *
     * @var RedisFactory
     */
    public $redis;

    /**
     * Create a new command queue instance.
     *
     * @param  RedisFactory  $redis
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
     */
    public function push($name, $command, array $options = [])
    {
        $this->connection()->rpush($name, json_encode([
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
        $length = $this->connection()->llen($name);

        if ($length < 1) {
            return [];
        }

        $results = $this->connection()->pipeline(function ($pipe) use ($name, $length) {
            $pipe->lrange($name, 0, $length - 1);

            $pipe->ltrim($name, $length, -1);
        });

        return collect($results[0])->map(function ($result) {
            return (object) json_decode($result, true);
        })->all();
    }

    /**
     * Flush the command queue for a given queue name.
     *
     * @param  string  $name
     * @return void
     */
    public function flush($name)
    {
        $this->connection()->del($name);
    }

    /**
     * Get the Redis connection instance.
     *
     * @return \Illuminate\Redis\Connetions\Connection
     */
    protected function connection()
    {
        return $this->redis->connection('horizon-command-queue');
    }
}
