<?php

namespace Laravel\Horizon;

use Illuminate\Support\Facades\Cache;

trait Pause
{
    protected $isPaused = false;

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
}
