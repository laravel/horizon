<?php

namespace Laravel\Horizon\Events;

use Laravel\Horizon\MasterSupervisor;

class MasterSupervisorLooped
{
    /**
     * The master supervisor instance.
     *
     * @var MasterSupervisor
     */
    public $master;

    /**
     * Create a new event instance.
     *
     * @param  Supervisor  $master
     * @return void
     */
    public function __construct(MasterSupervisor $master)
    {
        $this->master = $master;
    }
}
