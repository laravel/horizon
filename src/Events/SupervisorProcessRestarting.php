<?php

namespace Laravel\Horizon\Events;

use Laravel\Horizon\SupervisorProcess;

class SupervisorProcessRestarting
{
    /**
     * The supervisor process instance.
     *
     * @var SupervisorProcess
     */
    public $process;

    /**
     * Create a new event instance.
     *
     * @param  SupervisorProcess  $process
     * @return void
     */
    public function __construct(SupervisorProcess $process)
    {
        $this->process = $process;
    }
}
