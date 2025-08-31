<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Events\JobFailed;

class StoreTagsForFailedJob
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
     * @param  \Laravel\Horizon\Events\JobFailed  $event
     * @return void
     */
    public function handle(JobFailed $event)
    {
        $tags = collect($event->payload->tags())->map(function ($tag) {
            return 'failed:'.$tag;
        })->all();

        $this->tags->addTemporary(
            config('horizon.trim.failed', 2880), $event->payload->id(), $tags
        );
    }
}
