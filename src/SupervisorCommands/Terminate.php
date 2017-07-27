<?php

namespace Laravel\Horizon\SupervisorCommands;

use Laravel\Horizon\Contracts\Terminable;

class Terminate
{
    /**
     * Process the command.
     *
     * @param  \Laravel\Horizon\Contracts\Terminable  $terminable
     * @param  array  $options
     * @return void
     */
    public function process(Terminable $terminable, array $options)
    {
        $terminable->terminate($options['status'] ?? 0);
    }
}
