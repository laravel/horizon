<?php

namespace Laravel\Horizon\Listeners;

use Carbon\CarbonImmutable;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Events\MasterSupervisorLooped;

class TrimTags
{
    /**
     * The last time the tags were trimmed.
     *
     * @var \Carbon\CarbonImmutable
     */
    public $lastTrimmed;

    /**
     * How many minutes to wait in between each trim.
     *
     * @var int
     */
    public $frequency = 5;

    /**
     * Handle the event.
     *
     * @param  \Laravel\Horizon\Events\MasterSupervisorLooped  $event
     * @return void
     */
    public function handle(MasterSupervisorLooped $event)
    {
        if (! isset($this->lastTrimmed)) {
            $this->lastTrimmed = CarbonImmutable::now()->subMinutes($this->frequency + 1);
        }

        if ($this->lastTrimmed->lte(CarbonImmutable::now()->subMinutes($this->frequency))) {
            app(TagRepository::class)->trimExpired();

            $this->lastTrimmed = CarbonImmutable::now();
        }
    }
}
