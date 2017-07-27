<?php

namespace Laravel\Horizon\Http\Controllers;

use Laravel\Horizon\WaitTimeCalculator;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;

class DashboardStatsController extends Controller
{
    /**
     * Get the key performance stats for the dashboard.
     *
     * @return array
     */
    public function index()
    {
        return [
            'jobsPerMinute' => resolve(MetricsRepository::class)->jobsProcessedPerMinute(),
            'processes' => $this->totalProcessCount(),
            'queueWithMaxRuntime' => resolve(MetricsRepository::class)->queueWithMaximumRuntime(),
            'queueWithMaxThroughput' => resolve(MetricsRepository::class)->queueWithMaximumThroughput(),
            'recentlyFailed' => resolve(JobRepository::class)->countRecentlyFailed(),
            'recentJobs' => resolve(JobRepository::class)->countRecent(),
            'status' => $this->currentStatus(),
            'wait' => collect(resolve(WaitTimeCalculator::class)->calculate())->take(1),
        ];
    }

    /**
     * Get the total process count across all supervisors.
     *
     * @return int
     */
    protected function totalProcessCount()
    {
        $supervisors = resolve(SupervisorRepository::class)->all();

        return collect($supervisors)->reduce(function ($carry, $supervisor) {
            return $carry + collect($supervisor->processes)->sum();
        }, 0);
    }

    /**
     * Get the current status of Horizon.
     *
     * @return string
     */
    protected function currentStatus()
    {
        if (! $masters = resolve(MasterSupervisorRepository::class)->all()) {
            return 'inactive';
        }

        return collect($masters)->contains(function ($master) {
            return $master->status === 'paused';
        }) ? 'paused' : 'running';
    }
}
