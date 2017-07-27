<?php

namespace Laravel\Horizon\Contracts;

interface MetricsRepository
{
    /**
     * Get all of the class names that have metrics measurements.
     *
     * @return array
     */
    public function measuredJobs();

    /**
     * Get all of the queues that have metrics measurements.
     *
     * @return array
     */
    public function measuredQueues();

    /**
     * Get the jobs processed per minute since the last snapshot.
     *
     * @return int
     */
    public function jobsProcessedPerMinute();

    /**
     * Get the application's total throughput since the last snapshot.
     *
     * @return int
     */
    public function throughput();

    /**
     * Get the throughput for a given job.
     *
     * @param  string  $job
     * @return int
     */
    public function throughputForJob($job);

    /**
     * Get the throughput for a given queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function throughputForQueue($queue);

    /**
     * Get the average runtime for a given job in milliseconds.
     *
     * @param  string  $job
     * @return float
     */
    public function runtimeForJob($job);

    /**
     * Get the average runtime for a given queue in milliseconds.
     *
     * @param  string  $queue
     * @return float
     */
    public function runtimeForQueue($queue);

    /**
     * Get the queue that has the longest runtime.
     *
     * @return int
     */
    public function queueWithMaximumRuntime();

    /**
     * Get the queue that has the most throughput.
     *
     * @return int
     */
    public function queueWithMaximumThroughput();

    /**
     * Increment the metrics information for a job.
     *
     * @param  string  $job
     * @param  float  $runtime
     * @return void
     */
    public function incrementJob($job, $runtime);

    /**
     * Increment the metrics information for a queue.
     *
     * @param  string  $queue
     * @param  float  $runtime
     * @return void
     */
    public function incrementQueue($queue, $runtime);

    /**
     * Get all of the snapshots for the given job.
     *
     * @param  string  $job
     * @return array
     */
    public function snapshotsForJob($job);

    /**
     * Get all of the snapshots for the given queue.
     *
     * @param  string  $queue
     * @return array
     */
    public function snapshotsForQueue($queue);

    /**
     * Store a snapshot of the metrics information.
     *
     * @return void
     */
    public function snapshot();

    /**
     * Attempt to acquire a lock to monitor the queue wait times.
     *
     * @return bool
     */
    public function acquireWaitTimeMonitorLock();

    /**
     * Clear the metrics for a key.
     *
     * @param  string  $key
     * @return void
     */
    public function forget($key);
}
