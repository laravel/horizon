<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Events\JobPushed;
use Laravel\Horizon\Contracts\TagRepository;

class StoreMonitoredTags
{
    /**
     * The monitored tag repository.
     *
     * @var MonitoredTagRepository
     */
    public $monitored;

    /**
     * The tag repository implementation.
     *
     * @var TagRepository
     */
    public $tags;

    /**
     * Create a new listener instance.
     *
     * @param  TagRepository  $jobs
     * @return void
     */
    public function __construct(TagRepository $tags)
    {
        $this->tags = $tags;
    }

    /**
     * Handle the event.
     *
     * @param  JobPushed  $event
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
