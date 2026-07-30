<?php

namespace Laravel\Horizon\Contracts;

interface WorkloadRepository
{
    /**
     * Get the current workload of each queue.
     *
     * The built-in RedisWorkloadRepository always includes a non-empty
     * "connection" string so queue pause/resume can target the correct
     * connection. Custom implementations may omit it for legacy shapes;
     * consumers must treat a missing, empty, or non-string connection as
     * unavailable rather than guessing a default.
     *
     * @return array<int, array{
     *     name: string,
     *     connection?: string,
     *     length: int,
     *     wait: int,
     *     processes: int,
     *     split_queues: null|array<int, array{name: string, wait: int, length: int}>
     * }>
     */
    public function get();
}
