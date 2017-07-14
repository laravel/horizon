<?php

namespace Laravel\Horizon\SupervisorCommands;

use Laravel\Horizon\Contracts\Pausable;

class ContinueWorking
{
    /**
     * Process the command.
     *
     * @param  Pausable  $pausable
     * @return void
     */
    public function process(Pausable $pausable)
    {
        $pausable->continue();
    }
}
