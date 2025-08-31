<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Events\JobsMigrated;

class MarkJobsAsMigrated
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
     * @param  \Laravel\Horizon\Events\JobsMigrated  $event
     * @return void
     */
    public function handle(JobsMigrated $event)
    {
        $this->jobs->migrated($event->connectionName, $event->queue, $event->payloads);
    }
}
