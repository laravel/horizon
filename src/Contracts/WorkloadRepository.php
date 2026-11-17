<?php

namespace Laravel\Horizon\Contracts;

interface WorkloadRepository
{
    /**
     * Get the current workload of each queue.
     *
     * @return array<int, array{"name": string, "connection": string, "length": int, "wait": int, "processes": int, "paused": bool, "split_queues": null|array<int, array{"name": string, "wait": int, "length": int, "paused": bool}>}>
     */
    public function get();
}
