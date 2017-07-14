<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Events\JobsMigrated;
use Laravel\Horizon\Contracts\JobRepository;

class MarkJobsAsMigrated
{
    /**
     * The job repository implementation.
     *
     * @var JobRepository
     */
    public $jobs;

    /**
     * Create a new listener instance.
     *
     * @param  JobRepository  $jobs
     * @return void
     */
    public function __construct(JobRepository $jobs)
    {
        $this->jobs = $jobs;
    }

    /**
     * Handle the event.
     *
     * @param  JobsMigrated  $event
     * @return void
     */
    public function handle(JobsMigrated $event)
    {
        $this->jobs->migrated($event->connectionName, $event->queue, $event->payloads);
    }
}
