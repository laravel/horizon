<?php

namespace Laravel\Horizon\SupervisorCommands;

use Laravel\Horizon\Contracts\Restartable;

class Restart
{
    /**
     * Process the command.
     *
     * @param  \Laravel\Horizon\Contracts\Restartable  $restartable
     * @return void
     */
    public function process(Restartable $restartable)
    {
        $restartable->restart();
    }
}
