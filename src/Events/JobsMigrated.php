<?php

namespace Laravel\Horizon\Events;

use Laravel\Horizon\JobPayload;

class JobsMigrated
{
    /**
     * The connection name.
     *
     * @var string
     */
    public $connectionName;

    /**
     * The queue name.
     *
     * @var string
     */
    public $queue;

    /**
     * The job payloads that were migrated.
     *
     * @var \Illuminate\Support\Collection
     */
    public $payloads;

    /**
     * Create a new event instance.
     *
     * @param  array  $payloads
     * @return void
     */
    public function __construct($payloads)
    {
        $this->payloads = collect($payloads)->map(function ($job) {
            return new JobPayload($job);
        });
    }

    /**
     * Set the connection name.
     *
     * @param  string  $connectionName
     * @return $this
     */
    public function connection($connectionName)
    {
        $this->connectionName = $connectionName;

        return $this;
    }

    /**
     * Set the queue name.
     *
     * @param  string  $queue
     * @return $this
     */
    public function queue($queue)
    {
        $this->queue = $queue;

        return $this;
    }
}
