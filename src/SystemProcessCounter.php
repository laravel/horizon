<?php

namespace Laravel\Horizon;

use Laravel\Horizon\Factories\ProcessFactory;

class SystemProcessCounter
{
    /**
     * The base command to search for.
     *
     * @var string
     */
    public static $command = 'horizon:work';

    /**
     * Get the number of Horizon workers for a given supervisor.
     *
     * @param  string  $name
     * @return int
     */
    public function get($name)
    {
        $process = ProcessFactory::createProcess('exec ps aux | grep '.static::$command, null, ['COLUMNS' => '2000']);

        $process->run();

        return substr_count($process->getOutput(), 'supervisor='.$name);
    }
}
