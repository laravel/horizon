<?php

namespace Laravel\Horizon\Events;

use Laravel\Horizon\WorkerProcess;

class UnableToLaunchProcess
{
    /**
     * The worker process instance.
     *
     * @var \Laravel\Horizon\WorkerProcess
     */
    public $process;

    /**
     * Create a new event instance.
     *
     * @param  \Laravel\Horizon\WorkerProcess  $process
     * @return void
     */
    public function __construct(WorkerProcess $process)
    {
        $this->process = $process;
    }
}
