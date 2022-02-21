<?php

namespace Laravel\Horizon;

class Stopwatch
{
    /**
     * All of the current timers.
     *
     * @var array
     */
    public $timers = [];

    /**
     * Start a new timer.
     *
     * @param  string  $key
     * @return void
     */
    public function start($key)
    {
        $this->timers[$key] = microtime(true);
    }

    /**
     * Check a given timer and get the elapsed time in milliseconds.
     *
     * @param  string  $key
     * @return float|null
     */
    public function check($key)
    {
        if (isset($this->timers[$key])) {
            return round((microtime(true) - $this->timers[$key]) * 1000, 2);
        }
    }

    /**
     * Forget a given timer.
     *
     * @param  string  $key
     * @return void
     */
    public function forget($key)
    {
        unset($this->timers[$key]);
    }
}
