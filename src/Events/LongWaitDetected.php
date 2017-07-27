<?php

namespace Laravel\Horizon\Events;

use Laravel\Horizon\Notifications\LongWaitDetected as LongWaitDetectedNotification;

class LongWaitDetected
{
    /**
     * The queue connection name.
     *
     * @var string
     */
    public $connection;

    /**
     * The queue name.
     *
     * @var string
     */
    public $queue;

    /**
     * The wait time in seconds.
     *
     * @var int
     */
    public $seconds;

    /**
     * Create a new event instance.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  int  $seconds
     * @return void
     */
    public function __construct($connection, $queue, $seconds)
    {
        $this->queue = $queue;
        $this->seconds = $seconds;
        $this->connection = $connection;
    }

    /**
     * Get a notification representation of the event.
     *
     * @return \Laravel\Horizon\Notifications\LongWaitDetected
     */
    public function toNotification()
    {
        return new LongWaitDetectedNotification(
            $this->connection, $this->queue, $this->seconds
        );
    }
}
