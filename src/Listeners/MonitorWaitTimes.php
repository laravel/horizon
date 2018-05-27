<?php

namespace Laravel\Horizon\Listeners;

use Cake\Chronos\Chronos;
use Laravel\Horizon\WaitTimeCalculator;
use Laravel\Horizon\Events\LongWaitDetected;
use Laravel\Horizon\Events\SupervisorLooped;
use Laravel\Horizon\Contracts\MetricsRepository;

class MonitorWaitTimes
{
    /**
     * The metrics repository implementation.
     *
     * @var \Laravel\Horizon\Contracts\MetricsRepository
     */
    public $metrics;

    /**
     * The time at which we last checked if monitoring was due.
     *
     * @var \Cake\Chronos\Chronos
     */
    public $lastMonitored;

    /**
     * Create a new listener instance.
     *
     * @param  \Laravel\Horizon\Contracts\MetricsRepository  $metrics
     * @return void
     */
    public function __construct(MetricsRepository $metrics)
    {
        $this->metrics = $metrics;
    }

    /**
     * Handle the event.
     *
     * @param  \Laravel\Horizon\Events\SupervisorLooped  $event
     * @return void
     */
    public function handle(SupervisorLooped $event)
    {
        if (! $this->dueToMonitor()) {
            return;
        }

        // Here we will calculate the wait time in seconds for each of the queues that
        // the application is working. Then, we will filter the results to find the
        // queues with the longest wait times and raise events for each of these.
        $results = app(WaitTimeCalculator::class)->calculate();

        $long = collect($results)->filter(function ($wait, $queue) {
            return $wait > (config("horizon.waits.{$queue}") ?? 60);
        });

        // Once we have determined which queues have long wait times we will raise the
        // events for each of the queues. We'll need to separate the connection and
        // queue names into their own strings before we will fire off the events.
        $long->each(function ($wait, $queue) {
            [$connection, $queue] = explode(':', $queue, 2);

            event(new LongWaitDetected($connection, $queue, $wait));
        });
    }

    /**
     * Determine if monitoring is due.
     *
     * @return bool
     */
    protected function dueToMonitor()
    {
        // We will keep track of the amount of time between attempting to acquire the
        // lock to monitor the wait times. We only want a single supervisor to run
        // the checks on a given interval so that we don't fire too many events.
        if (! $this->lastMonitored) {
            $this->lastMonitored = Chronos::now();
        }

        if (! $this->timeToMonitor()) {
            return false;
        }

        // Next we will update the monitor timestamp and attempt to acquire a lock to
        // check the wait times. We use Redis to do it in order to have the atomic
        // operation required. This will avoid any deadlocks or race conditions.
        $this->lastMonitored = Chronos::now();

        return $this->metrics->acquireWaitTimeMonitorLock();
    }

    /**
     * Determine if enough time has elapsed to attempt to monitor.
     *
     * @return bool
     */
    protected function timeToMonitor()
    {
        return Chronos::now()->subMinutes(1)->lte($this->lastMonitored);
    }
}
