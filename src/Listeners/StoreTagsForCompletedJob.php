<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Events\JobDeleted;

class StoreTagsForCompletedJob
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
        if ($event->job->hasFailed() || $event->payload->isSilenced()) {
            return;
        }

        $jobTags = $event->payload->tags();
        $jobId = $event->payload->id();

        $completedTags = collect($jobTags)
            ->map(fn ($tag) => 'completed_jobs:'.$tag)
            ->all();

        $this->tags->addTemporary(
            config('horizon.trim.recent', 60), $jobId, $completedTags
        );

        $pendingTags = collect($jobTags)
            ->map(fn ($tag) => 'pending_jobs:'.$tag)
            ->all();

        $this->tags->forgetJobs($pendingTags, $jobId);
    }
}
