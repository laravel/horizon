<?php

declare(strict_types=1);

namespace Laravel\Horizon\Events;

use Laravel\Horizon\MasterSupervisor;

class MasterSupervisorOutOfMemoryTerminated
{
    /**
     * The master supervisor instance.
     *
     * @var \Laravel\Horizon\MasterSupervisor
     */
    public $master;

    /**
     * Create a new event instance.
     *
     * @param  \Laravel\Horizon\MasterSupervisor  $master
     * @return void
     */
    public function __construct(MasterSupervisor $master)
    {
        $this->master = $master;
    }
}
