<?php

namespace Laravel\Horizon\Contracts;

interface Pausable
{
    /**
     * Pause the process.
     *
     * @return void
     */
    public function pause();

    /**
     * Instruct the process to continue working.
     *
     * @return void
     */
    public function continue();
}
