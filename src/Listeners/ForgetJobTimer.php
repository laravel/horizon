<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Stopwatch;

class ForgetJobTimer
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
     * @param  \Illuminate\Queue\Events\JobExceptionOccurred|\Illuminate\Queue\Events\JobFailed  $event
     * @return void
     */
    public function handle($event)
    {
        $this->watch->forget($event->job->getJobId());
    }
}
