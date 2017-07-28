<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Stopwatch;
use Laravel\Horizon\Events\JobReserved;

class StartTimingJob
{
    /**
     * The stopwatch instance.
     *
     * @var \Laravel\Horizon\Stopwatch
     */
    public $watch;

    /**
     * Create a new listener instance.
     *
     * @param  \Laravel\Horizon\Stopwatch  $watch
     * @return void
     */
    public function __construct(Stopwatch $watch)
    {
        $this->watch = $watch;
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
