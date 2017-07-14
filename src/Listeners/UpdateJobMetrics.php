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
     * @var MetricsRepository
     */
    public $metrics;

    /**
     * The stopwatch instance.
     *
     * @var Stopwatch
     */
    public $watch;

    /**
     * Create a new listener instance.
     *
     * @param  MetricsRepository  $metrics
     * @param  Stopwatch  $watch
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
            $event->payload->commandName(), $time
        );
    }
}
