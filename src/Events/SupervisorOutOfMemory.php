<?php

namespace Laravel\Horizon\Events;

use Laravel\Horizon\Supervisor;

class SupervisorOutOfMemory
{
    /**
     * The supervisor instance.
     *
     * @var \Laravel\Horizon\Supervisor
     */
    public $supervisor;

    /**
     * The memory usage that exceeded the allowable limit.
     *
     * @var int|float
     */
    public $memoryUsage;

    /**
     * Create a new event instance.
     *
     * @param  \Laravel\Horizon\Supervisor  $supervisor
     * @return void
     */
    public function __construct(Supervisor $supervisor)
    {
        $this->supervisor = $supervisor;
    }

    /**
     * Get the memory usage that triggered the event.
     *
     * @return int|float
     */
    public function getMemoryUsage()
    {
        return $this->memoryUsage ?? $this->supervisor->memoryUsage();
    }

    /**
     * Set the memory usage that was recorded when the event was dispatched.
     *
     * @param  int|float $memoryUsage
     * @return $this
     */
    public function setMemoryUsage($memoryUsage)
    {
        $this->memoryUsage = $memoryUsage;

        return $this;
    }
}
