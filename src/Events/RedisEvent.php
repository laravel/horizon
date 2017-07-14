<?php

namespace Laravel\Horizon\Events;

use Laravel\Horizon\JobPayload;

class RedisEvent
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
     * The job payload.
     *
     * @var JobPayload
     */
    public $payload;

    /**
     * Create a new event instance.
     *
     * @param  string  $payload
     * @return void
     */
    public function __construct($payload)
    {
        $this->payload = new JobPayload($payload);
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
