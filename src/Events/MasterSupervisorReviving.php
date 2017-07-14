<?php

namespace Laravel\Horizon\Events;

class MasterSupervisorReviving
{
    /**
     * The master supervisor that was dead.
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
