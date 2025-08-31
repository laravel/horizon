<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Events\JobReserved;
use Laravel\Horizon\Stopwatch;

class StartTimingJob
{
    /**
     * Create a new listener instance.
     *
     * @param  \Laravel\Horizon\Stopwatch  $watch  The stopwatch instance.
     * @return void
     */
    public function __construct(
        public Stopwatch $watch,
    ) {
    }

    /**
     * Handle the event.
     *
     * @param  \Laravel\Horizon\Events\JobReserved  $event
     * @return void
     */
    public function handle(JobReserved $event)
    {
        $this->watch->start($event->payload->id());
    }
}
