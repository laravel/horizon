<?php

namespace Laravel\Horizon\Listeners;

use Carbon\CarbonImmutable;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Events\ManyPendingJobsDetected;
use Laravel\Horizon\Lock;

class MonitorManyPendingJobs
{
    /**
     * The time at which we last checked if monitoring was due.
     */
    public ?CarbonImmutable $lastMonitored = null;

    public function handle(): void
    {
        if (! $this->dueToMonitor()) {
            return;
        }

        $amountPendingJobsThreshold = config('horizon.pending_jobs_monitor_threshold');

        if ($amountPendingJobsThreshold === 0 || $amountPendingJobsThreshold === null) {
            return;
        }

        $amountPendingJobs = app(JobRepository::class)->countPending();

        if ($amountPendingJobs <= $amountPendingJobsThreshold) {
            return;
        }

        event(new ManyPendingJobsDetected($amountPendingJobs));
    }

    /**
     * Determine if monitoring is due.
     */
    protected function dueToMonitor(): bool
    {
        // We will keep track of the amount of time between attempting to acquire the lock to
        // monitor the amount of pending jobs. We only want a single supervisor to run
        // the checks on a given interval so that we don't fire too many events.
        if (! $this->timeToMonitor()) {
            return false;
        }

        $lock = app(Lock::class)->get('monitor:many-pending-jobs');

        if (! $lock) {
            // If we cannot acquire the lock, it means another supervisor is already monitoring.
            return false;
        }

        // Next we will update the monitor timestamp and attempt to acquire a lock to check the
        // amount of pending jobs. We use Redis to do it in order to have the atomic
        // operation required. This will avoid any deadlocks or race conditions.
        $this->lastMonitored = CarbonImmutable::now();

        return true;
    }

    /**
     * Determine if enough time has elapsed to attempt to monitor.
     */
    protected function timeToMonitor(): bool
    {
        if ($this->lastMonitored === null) {
            return true;
        }

        return CarbonImmutable::now()->gte($this->lastMonitored->addMinute());
    }
}
