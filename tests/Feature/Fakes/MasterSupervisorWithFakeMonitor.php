<?php

namespace Laravel\Horizon\Tests\Feature\Fakes;

class MasterSupervisorWithFakeMonitor extends MasterSupervisorWithFakeExit
{
    public $exited = false;

    /**
     * Monitor the worker processes.
     *
     * @return void
     */
    public function monitor()
    {
        $this->ensureNoOtherMasterSupervisors();

        $this->listenForSignals();

        $this->persist();

        $this->loop();
        $this->loop();

        $this->terminate();
    }
}
