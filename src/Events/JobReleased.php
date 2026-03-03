<?php

namespace Laravel\Horizon\Events;

class JobReleased extends RedisEvent
{
    /**
     * The delay in seconds before the job becomes available.
     *
     * @var int
     */
    public $delay;

    /**
     * Create a new event instance.
     *
     * @param  string  $payload
     * @param  int  $delay
     * @return void
     */
    public function __construct($payload, $delay = 0)
    {
        parent::__construct($payload);

        $this->delay = $delay;
    }
}
