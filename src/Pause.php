<?php

namespace Laravel\Horizon;

use Illuminate\Support\Facades\Cache;

trait Pause
{
    protected $isPaused = false;

    /**
     * Process pause from cache.
     *
     * @return void
     */
    protected function processPause()
    {
        $isPaused = Cache::get('horizon:pause', false);

        if ($this->isPaused === $isPaused) {
            return;
        }

        match ($isPaused) {
            true => $this->pause(),
            false => $this->continue(),
        };

        $this->isPaused = $isPaused;
    }

    /**
     * Pause all supervisors and worker processes.
     *
     * @return void
     */
    abstract protected function pause();

    /**
     * Instruct the supervisors and worker processes to continue working.
     *
     * @return void
     */
    abstract protected function continue();
}
