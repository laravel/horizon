<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Events\JobReleased;

class MarkJobAsReleased
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
     * @param  \Laravel\Horizon\Events\JobReleased  $event
     * @return void
     */
    public function handle(JobReleased $event)
    {
        $this->jobs->released($event->connectionName, $event->queue, $event->payload);
    }
}
