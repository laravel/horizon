<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Events\MasterSupervisorLooped;

class ExpireSupervisors
{
    /**
     * Handle the event.
     *
     * @param  \Laravel\Horizon\Events\MasterSupervisorLooped  $event
     * @return void
     */
    public function handle(MasterSupervisorLooped $event)
    {
        app(MasterSupervisorRepository::class)->flushExpired();

        app(SupervisorRepository::class)->flushExpired();
    }
}
