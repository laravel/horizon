<?php

namespace Laravel\Horizon\Events;

class JobFailed extends RedisEvent
{
    /**
     * The exception that caused the failure.
     *
     * @var \Exception
     */
    public $exception;

    /**
     * The queue job instance.
     *
     * @var \Illuminate\Queue\Jobs\Job
     */
    public $job;

    /**
     * Create a new event instance.
     *
     * @param  \Exception  $exception
     * @param  \Illuminate\Queue\Jobs\Job  $job
     * @param  string  $payload
     * @return void
     */
    public function __construct($exception, $job, $payload)
    {
        $this->job = $job;
        $this->exception = $exception;

        parent::__construct($payload);
    }
}
