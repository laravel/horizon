<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Events\JobFailed;
use Laravel\Horizon\Contracts\JobRepository;

class MarkJobAsFailed
{
    /**
     * The job repository implementation.
     *
     * @var JobRepository
     */
    public $jobs;

    /**
     * Create a new listener instance.
     *
     * @param  JobRepository  $jobs
     * @return void
     */
    public function __construct(JobRepository $jobs)
    {
        $this->jobs = $jobs;
    }

    /**
     * Handle the event.
     *
     * @param  JobFailed  $event
     * @return void
     */
    public function handle(JobFailed $event)
    {
        $this->jobs->failed(
            $event->exception, $event->connectionName,
            $event->queue, $event->payload
        );
    }
}
