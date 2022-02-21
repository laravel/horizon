<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Stopwatch;

class ClearJobTimers
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
     * @return void
     */
    public function handle()
    {
        $this->watch->clear();
    }
}
