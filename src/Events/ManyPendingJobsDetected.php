<?php

namespace Laravel\Horizon\Events;

use Illuminate\Container\Container;
use Laravel\Horizon\Contracts\ManyPendingJobsDetectedNotification;

class ManyPendingJobsDetected
{
    public function __construct(public int $amountPendingJobs)
    {
        //
    }

    /**
     * Get a notification representation of the event.
     */
    public function toNotification(): ManyPendingJobsDetectedNotification
    {
        return Container::getInstance()->make(ManyPendingJobsDetectedNotification::class, [
            'amountPendingJobs' => $this->amountPendingJobs,
        ]);
    }
}
