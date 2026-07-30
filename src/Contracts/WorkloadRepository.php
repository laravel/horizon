<?php

namespace Laravel\Horizon\Contracts;

interface WorkloadRepository
{
    /**
     * Get the current workload of each queue.
     *
     * @return array<int, array{"connection": string, "name": string, "length": int, "reserved": int, "delayed": int, "wait": int, "processes": int, "throughput"?: int, "split_queues": null|array<int, array{"connection": string, "name": string, "wait": int, "length": int, "throughput"?: int}>}>
     */
    public function get();
}
