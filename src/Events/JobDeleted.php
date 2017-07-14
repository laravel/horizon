<?php

namespace Laravel\Horizon\Events;

class JobDeleted extends RedisEvent
{
    /**
     * The queue job instance.
     *
     * @var \Illuminate\Queue\Jobs\Job
     */
    public $job;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Queue\Jobs\Job  $job
     * @param  string  $payload
     * @return void
     */
    public function __construct($job, $payload)
    {
        $this->job = $job;

        parent::__construct($payload);
    }
}
