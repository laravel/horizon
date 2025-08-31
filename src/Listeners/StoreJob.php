<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Events\JobPushed;

class StoreJob
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
     * @param  \Laravel\Horizon\Events\JobPushed  $event
     * @return void
     */
    public function handle(JobPushed $event)
    {
        $this->jobs->pushed(
            $event->connectionName, $event->queue, $event->payload
        );
    }
}
