<?php

namespace Laravel\Horizon\SupervisorCommands;

use Laravel\Horizon\Supervisor;

class Scale
{
    /**
     * Process the command.
     *
     * @param  \Laravel\Horizon\Supervisor  $supervisor
     * @param  array  $options
     * @return void
     */
    public function process(Supervisor $supervisor, array $options)
    {
        $supervisor->scale($options['scale']);
    }
}
