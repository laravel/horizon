<?php

namespace Laravel\Horizon;

class Exec
{
    /**
     * Run the given command.
     *
     * @param  string  $command
     * @return array
     */
    public function run($command)
    {
        exec($command, $output);

        return $output;
    }
}
