<?php

namespace Laravel\Horizon\Connectors;

use Illuminate\Queue\Connectors\RedisConnector as BaseConnector;
use Illuminate\Support\Arr;
use Laravel\Horizon\RedisQueue;

class RedisConnector extends BaseConnector
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Laravel\Horizon\RedisQueue
     */
    public function connect(array $config)
    {
        return new RedisQueue(
            $this->redis, $config['queue'],
            Arr::get($config, 'connection', $this->connection),
            Arr::get($config, 'retry_after', 60),
            Arr::get($config, 'block_for', null)
        );
    }
}
