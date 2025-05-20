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
     * All of the initial memory readings.
     *
     * @var array
     */
    public $memory = [];

    /**
     * Start a new timer.
     *
     * @param  string  $key
     * @return void
     */
    public function start($key)
    {
        $this->timers[$key] = microtime(true);
        $this->memory[$key] = memory_get_usage(true);
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
     * Check the memory usage for a given job in megabytes.
     *
     * @param  string  $key
     * @return float|null
     */
    public function checkMemory($key)
    {
        if (isset($this->memory[$key])) {
            $memoryUsed = memory_get_peak_usage(true) - $this->memory[$key];
            return round($memoryUsed / 1024 / 1024, 2);
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
        unset($this->memory[$key]);
    }
}
