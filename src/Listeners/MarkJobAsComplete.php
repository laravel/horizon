<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Events\JobDeleted;

class MarkJobAsComplete
{
    /**
     * Create a new listener instance.
     *
     * @param  \Laravel\Horizon\Contracts\JobRepository  $jobs  The job repository implementation.
     * @param  \Laravel\Horizon\Contracts\TagRepository  $tags  The tag repository implementation.
     * @return void
     */
    public function __construct(
        public JobRepository $jobs,
        public TagRepository $tags,
    ) {
    }

    /**
     * Handle the event.
     *
     * @param  \Laravel\Horizon\Events\JobDeleted  $event
     * @return void
     */
    public function handle(JobDeleted $event)
    {
        $this->jobs->completed($event->payload, $event->job->hasFailed(), $event->payload->isSilenced());

        if (! $event->job->hasFailed() && count($this->tags->monitored($event->payload->tags())) > 0) {
            $this->jobs->remember($event->connectionName, $event->queue, $event->payload);
        }
    }
}
