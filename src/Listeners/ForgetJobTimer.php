<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Stopwatch;

class ForgetJobTimer
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
     * @param  \Illuminate\Queue\Events\JobExceptionOccurred|\Illuminate\Queue\Events\JobFailed  $event
     * @return void
     */
    public function handle($event)
    {
        $this->watch->forget($event->job->getJobId());
    }
}
