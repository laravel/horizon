<?php

namespace Laravel\Horizon\Listeners;

use Cake\Chronos\Chronos;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Events\MasterSupervisorLooped;

class TrimFailedJobs
{
    /**
     * The last time the recent jobs were trimmed.
     *
     * @var \Cake\Chronos\Chronos
     */
    public $lastTrimmed;

    /**
     * How many hours to wait in between each trim.
     *
     * @var int
     */
    public $frequency = 23;

    /**
     * Handle the event.
     *
     * @param  \Laravel\Horizon\Events\MasterSupervisorLooped  $event
     * @return void
     */
    public function handle(MasterSupervisorLooped $event)
    {
        if (! isset($this->lastTrimmed)) {
            $this->lastTrimmed = Chronos::now()->subHours($this->frequency + 1);
        }

        if ($this->lastTrimmed->lte(Chronos::now()->subHours($this->frequency))) {
            resolve(JobRepository::class)->trimFailedJobs();

            $this->lastTrimmed = Chronos::now();
        }
    }
}
