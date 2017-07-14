<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Events\JobDeleted;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\TagRepository;

class MarkJobAsComplete
{
    /**
     * The job repository implementation.
     *
     * @var JobRepository
     */
    public $jobs;

    /**
     * The tag repository implementation.
     *
     * @var TagRepository
     */
    public $tags;

    /**
     * Create a new listener instance.
     *
     * @param  JobRepository  $jobs
     * @param  MonitoredTagRepository  $tags
     * @return void
     */
    public function __construct(JobRepository $jobs, TagRepository $tags)
    {
        $this->jobs = $jobs;
        $this->tags = $tags;
    }

    /**
     * Handle the event.
     *
     * @param  JobDeleted  $event
     * @return void
     */
    public function handle(JobDeleted $event)
    {
        $this->jobs->completed($event->payload, $event->job->hasFailed());

        if (! $event->job->hasFailed() && count($this->tags->monitored($event->payload->tags())) > 0) {
            $this->jobs->remember($event->connectionName, $event->queue, $event->payload);
        }
    }
}
