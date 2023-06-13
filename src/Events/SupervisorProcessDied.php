<?php

namespace Laravel\Horizon\Events;

use Laravel\Horizon\SupervisorProcess;

class SupervisorProcessDied
{
    /**
     * The supervisor process.
     *
     * @var \Laravel\Horizon\SupervisorProcess
     */
    public $supervisorProcess;

    /**
     * The code with which the process exited.
     *
     * @var int|null
     */
    public $exitCode;

    /**
     * Create a new event instance.
     *
     * @param  \Laravel\Horizon\SupervisorProcess  $supervisorProcess
     * @param  int|null  $exitCode
     * @return void
     */
    public function __construct(SupervisorProcess $supervisorProcess, ?int $exitCode)
    {
        $this->supervisorProcess = $supervisorProcess;
        $this->exitCode = $exitCode;
    }
}
