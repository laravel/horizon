<?php

namespace Laravel\Horizon\Tests\Feature\Fakes;

use Laravel\Horizon\Supervisor;

class SupervisorWithFakeExit extends Supervisor
{
    public $exited = false;

    /**
     * End the current PHP process.
     *
     * @param  int  $status
     * @return void
     */
    protected function exitProcess($status = 0)
    {
        $this->exited = true;
    }
}
