<?php

namespace Laravel\Horizon\Listeners;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Events\JobFailed as LaravelJobFailed;
use Illuminate\Queue\Jobs\RedisJob;
use Laravel\Horizon\Events\JobFailed;

class MarshalFailedEvent
{
    /**
     * Create a new listener instance.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events  The event dispatcher implementation.
     * @return void
     */
    public function __construct(
        public Dispatcher $events,
    ) {
    }

    /**
     * Handle the event.
     *
     * @param  \Illuminate\Queue\Events\JobFailed  $event
     * @return void
     */
    public function handle(LaravelJobFailed $event)
    {
        if (! $event->job instanceof RedisJob) {
            return;
        }

        $this->events->dispatch((new JobFailed(
            $event->exception, $event->job, $event->job->getReservedJob()
        ))->connection($event->connectionName)->queue($event->job->getQueue()));
    }
}
