<?php

namespace Laravel\Horizon\Listeners;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Events\JobFailed as LaravelJobFailed;
use Illuminate\Queue\Jobs\RedisJob;
use Laravel\Horizon\Events\JobFailed;
use Laravel\Horizon\Jobs\DatabaseJob;

class MarshalFailedEvent
{
    /**
     * The event dispatcher implementation.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    public $events;

    /**
     * Create a new listener instance.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Handle the event.
     *
     * @param  \Illuminate\Queue\Events\JobFailed  $event
     * @return void
     */
    public function handle(LaravelJobFailed $event)
    {
        $payload = match (true) {
            $event->job instanceof RedisJob => $event->job->getReservedJob(),
            $event->job instanceof DatabaseJob => $event->job->getRawBody(),
            default => null,
        };

        if (is_null($payload)) {
            return;
        }

        $this->events->dispatch((new JobFailed(
            $event->exception, $event->job, $payload
        ))->connection($event->connectionName)->queue($event->job->getQueue()));
    }
}
