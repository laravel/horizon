<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Events\JobDeleted;
use Laravel\Horizon\Stopwatch;

class UpdateJobMetrics
{
    /**
     * Create a new listener instance.
     *
     * @param  \Laravel\Horizon\Contracts\MetricsRepository  $metrics  The metrics repository implementation.
     * @param  \Laravel\Horizon\Stopwatch  $watch  The stopwatch instance.
     * @return void
     */
    public function __construct(
        public MetricsRepository $metrics,
        public Stopwatch $watch,
    ) {
    }

    /**
     * Stop gathering metrics for a job.
     *
     * @param  \Laravel\Horizon\Events\JobDeleted  $event
     * @return void
     */
    public function handle(JobDeleted $event)
    {
        if ($event->job->hasFailed()) {
            return;
        }

        $time = $this->watch->check($id = $event->payload->id()) ?: 0;

        $this->metrics->incrementQueue(
            $event->job->getQueue(), $time
        );

        $this->metrics->incrementJob(
            $event->payload->displayName(), $time
        );

        $this->watch->forget($id);
    }
}
