<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Events\JobFailed;

class MarkJobAsFailed
{
    /**
     * Create a new listener instance.
     *
     * @param  \Laravel\Horizon\Contracts\JobRepository  $jobs  The job repository implementation.
     * @return void
     */
    public function __construct(
        public JobRepository $jobs,
    ) {
    }

    /**
     * Handle the event.
     *
     * @param  \Laravel\Horizon\Events\JobFailed  $event
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
