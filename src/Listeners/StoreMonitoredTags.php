<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Events\JobPushed;

class StoreMonitoredTags
{
    /**
     * Create a new listener instance.
     *
     * @param  \Laravel\Horizon\Contracts\TagRepository  $tags  The tag repository implementation.
     * @return void
     */
    public function __construct(
        public TagRepository $tags,
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
        $monitoring = $this->tags->monitored($event->payload->tags());

        if (! empty($monitoring)) {
            $this->tags->add($event->payload->id(), $monitoring);
        }
    }
}
