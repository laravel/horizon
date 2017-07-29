<?php

namespace Laravel\Horizon\Listeners;

use Cake\Chronos\Chronos;
use Laravel\Horizon\Events\MasterSupervisorLooped;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;

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
        resolve(MasterSupervisorRepository::class)->flushExpired();

        resolve(SupervisorRepository::class)->flushExpired();
    }
}
