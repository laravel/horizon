<?php

namespace Laravel\Horizon\Events;

class MasterSupervisorDeployed
{
    /**
     * The master supervisor that was deployed.
     *
     * @var string
     */
    public $master;

    /**
     * Create a new event instance.
     *
     * @param  string  $master
     * @return void
     */
    public function __construct($master)
    {
        $this->master = $master;
    }
}
