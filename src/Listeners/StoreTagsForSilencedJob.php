<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Events\JobDeleted;

class StoreTagsForSilencedJob
{
    /**
     * The tag repository implementation.
     *
     * @var \Laravel\Horizon\Contracts\TagRepository
     */
    public $tags;

    /**
     * Create a new listener instance.
     *
     * @param  \Laravel\Horizon\Contracts\TagRepository  $tags
     * @return void
     */
    public function __construct(TagRepository $tags)
    {
        $this->tags = $tags;
    }

    /**
     * Handle the event.
     *
     * @param  \Laravel\Horizon\Events\JobDeleted  $event
     * @return void
     */
    public function handle(JobDeleted $event)
    {
        if ($event->job->hasFailed() || ! $event->payload->isSilenced()) {
            return;
        }

        $tags = collect($event->payload->tags())
            ->map(fn ($tag) => 'silenced_jobs:'.$tag)
            ->all();

        $this->tags->addTemporary(
            config('horizon.trim.recent', 60), $event->payload->id(), $tags
        );
    }
}
