<?php

namespace Laravel\Horizon\Connectors;

use Illuminate\Queue\Connectors\DatabaseConnector as BaseConnector;
use Laravel\Horizon\DatabaseQueue;

class DatabaseConnector extends BaseConnector
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Laravel\Horizon\DatabaseQueue
     */
    #[\Override]
    public function connect(array $config)
    {
        return new DatabaseQueue(
            $this->connections->connection($config['connection'] ?? null),
            $config['table'],
            $config['queue'],
            $config['retry_after'] ?? 60,
            $config['after_commit'] ?? null
        );
    }
}
