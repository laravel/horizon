<?php

namespace Laravel\Horizon\Listeners;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Events\JobFailed as LaravelJobFailed;
use Illuminate\Queue\Jobs\RedisJob;
use Laravel\Horizon\Events\JobFailed;
use Laravel\Horizon\Jobs\HorizonDatabaseJob;

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
        $payload = $this->payloadFor($event->job);

        if ($payload === null) {
            return;
        }

        $this->events->dispatch((new JobFailed(
            $event->exception, $event->job, $payload
        ))->connection($event->connectionName)->queue($event->job->getQueue()));
    }

    /**
     * Extract the raw payload from a Horizon-instrumented job, or null if untracked.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return string|null
     */
    protected function payloadFor($job)
    {
        return match (true) {
            $job instanceof RedisJob => $job->getReservedJob(),
            $job instanceof HorizonDatabaseJob => $job->getJobRecord()->payload,
            default => null,
        };
    }
}
