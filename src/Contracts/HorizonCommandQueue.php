<?php

namespace Laravel\Horizon\Contracts;

interface HorizonCommandQueue
{
    /**
     * Push a command onto a queue.
     *
     * @param  string  $name
     * @param  string  $command
     * @param  array  $options
     * @return void
     */
    public function push($name, $command, array $options = []);

    /**
     * Get the pending commands for a given queue name.
     *
     * @param  string  $name
     * @return array
     */
    public function pending($name);

    /**
     * Flush the command queue for a given queue name.
     *
     * @param  string  $name
     * @return void
     */
    public function flush($name);
}
