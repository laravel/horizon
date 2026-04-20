<?php

namespace Laravel\Horizon\Repositories;

use Illuminate\Redis\Connections\PhpRedisClusterConnection;

trait UsesClusterAwarePipeline
{
    /**
     * Execute commands in a pipeline, falling back to a transaction.
     *
     * @param  callable  $callback
     * @return array
     */
    protected function pipeline(callable $callback)
    {
        $connection = $this->connection();

        if ($connection instanceof PhpRedisClusterConnection) {
            return $connection->transaction($callback);
        }

        return $connection->pipeline($callback);
    }
}
