<?php

namespace Laravel\Horizon\Events;

use Laravel\Horizon\Supervisor;

class SupervisorLooped
{
    /**
     * The supervisor instance.
     *
     * @var \Laravel\Horizon\Supervisor
     */
    public $supervisor;

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
}
