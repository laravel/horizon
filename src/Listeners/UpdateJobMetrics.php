<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Stopwatch;
use Laravel\Horizon\Events\JobDeleted;
use Laravel\Horizon\Contracts\MetricsRepository;

class UpdateJobMetrics
{
    /**
     * The metrics repository implementation.
     *
     * @var \Laravel\Horizon\Contracts\MetricsRepository
     */
    public $metrics;

    /**
     * The stopwatch instance.
     *
     * @var \Laravel\Horizon\Stopwatch
     */
    public $watch;

    /**
     * Create a new listener instance.
     *
     * @param  \Laravel\Horizon\Contracts\MetricsRepository  $metrics
     * @param  \Laravel\Horizon\Stopwatch  $watch
     * @return void
     */
    public function __construct(MetricsRepository $metrics, Stopwatch $watch)
    {
        $this->watch = $watch;
        $this->metrics = $metrics;
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

        $time = $this->watch->check($event->payload->id());

        $this->metrics->incrementQueue(
            $event->job->getQueue(), $time
        );

        $this->metrics->incrementJob(
            $event->payload->displayName(), $time
        );
    }
}
