<?php

namespace Laravel\Horizon;

use Illuminate\Support\Arr;

trait ListensForSignals
{
    /**
     * The pending signals that need to be processed.
     *
     * @var array
     */
    protected $pendingSignals = [];

    /**
     * Listen for incoming process signals.
     *
     * @return void
     */
    protected function listenForSignals()
    {
        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, function () {
            $this->pendingSignals['terminate'] = 'terminate';
        });

        pcntl_signal(SIGUSR1, function () {
            $this->pendingSignals['restart'] = 'restart';
        });

        pcntl_signal(SIGUSR2, function () {
            $this->pendingSignals['pause'] = 'pause';
        });

        pcntl_signal(SIGCONT, function () {
            $this->pendingSignals['continue'] = 'continue';
        });
    }

    /**
     * Process the pending signals.
     *
     * @return void
     */
    protected function processPendingSignals()
    {
        while ($this->pendingSignals) {
            $signal = Arr::first($this->pendingSignals);

            $this->{$signal}();

            unset($this->pendingSignals[$signal]);
        }
    }
}
