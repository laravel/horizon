<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Contracts\WorkloadRepository;
use Laravel\Horizon\WaitTimeCalculator;

class DashboardStatsController extends Controller
{
    /**
     * Get the key performance stats for the dashboard.
     *
     * @return array
     */
    public function index()
    {
        $jobs = app(JobRepository::class);
        $metrics = app(MetricsRepository::class);

        return [
            'failedJobs' => $jobs->countRecentlyFailed(),
            'failedJobsPastHour' => $this->failedJobsPastHour($jobs),
            'failedJobsPastDay' => $this->failedJobsPastDay($jobs),
            'jobsPerMinute' => $metrics->jobsProcessedPerMinute(),
            'pausedMasters' => $this->totalPausedMasters(),
            'periods' => [
                'failedJobs' => config('horizon.trim.recent_failed', config('horizon.trim.failed')),
                'recentJobs' => config('horizon.trim.recent'),
            ],
            'processes' => $this->totalProcessCount(),
            'processing' => $this->processing(),
            'queueWithMaxRuntime' => $metrics->queueWithMaximumRuntime(),
            'queueWithMaxThroughput' => $metrics->queueWithMaximumThroughput(),
            'recentJobs' => $jobs->countRecent(),
            'status' => $this->currentStatus(),
            'wait' => collect(app(WaitTimeCalculator::class)->calculate())->take(1),
            'navigation' => [
                'monitoring' => count(app(TagRepository::class)->monitoring()),
                'metrics' => count($metrics->measuredJobs()) + count($metrics->measuredQueues()),
                'batches' => $this->navigationBatchCount(),
                'pending' => $jobs->countPending(),
                'completed' => $jobs->countCompleted(),
                'silenced' => $jobs->countSilenced(),
                'failed' => $jobs->countFailed(),
            ],
        ];
    }

    /**
     * Determine if Horizon is actively processing any jobs.
     *
     * @return bool
     */
    protected function processing()
    {
        $workload = app(WorkloadRepository::class);

        if (method_exists($workload, 'processing')) {
            return (bool) $workload->processing();
        }

        return collect($workload->get())->contains(
            fn ($queue) => ($queue['reserved'] ?? 0) > 0
        );
    }

    /**
     * Get the active batch count for sidebar navigation.
     *
     * @return int|null
     */
    protected function navigationBatchCount()
    {
        try {
            if (config('queue.batching.driver', 'database') === 'dynamodb') {
                return null;
            }

            return (int) DB::connection(config('queue.batching.database'))
                ->table(config('queue.batching.table', 'job_batches'))
                ->whereNull('cancelled_at')
                ->whereColumn('pending_jobs', '>', 'failed_jobs')
                ->count();
        } catch (QueryException $e) {
            return null;
        }
    }

    /**
     * Get the number of failed jobs recorded during the past hour.
     *
     * @param  \Laravel\Horizon\Contracts\JobRepository  $jobs
     * @return int|null
     */
    protected function failedJobsPastHour(JobRepository $jobs)
    {
        if (config('horizon.trim.failed', 10080) < 60 || ! method_exists($jobs, 'countFailedSince')) {
            return null;
        }

        return $jobs->countFailedSince(60);
    }

    /**
     * Get the number of failed jobs recorded during the past 24 hours.
     *
     * @param  \Laravel\Horizon\Contracts\JobRepository  $jobs
     * @return int|null
     */
    protected function failedJobsPastDay(JobRepository $jobs)
    {
        if (config('horizon.trim.failed', 10080) < 1440 || ! method_exists($jobs, 'countFailedSince')) {
            return null;
        }

        return $jobs->countFailedSince(1440);
    }

    /**
     * Get the total process count across all supervisors.
     *
     * @return int
     */
    protected function totalProcessCount()
    {
        $supervisors = app(SupervisorRepository::class)->all();

        return collect($supervisors)
            ->reduce(fn ($carry, $supervisor) => $carry + collect($supervisor->processes)->sum(), 0);
    }

    /**
     * Get the current status of Horizon.
     *
     * @return string
     */
    protected function currentStatus()
    {
        if (! $masters = app(MasterSupervisorRepository::class)->all()) {
            return 'inactive';
        }

        return collect($masters)
            ->every(fn ($master) => $master->status === 'paused') ? 'paused' : 'running';
    }

    /**
     * Get the number of master supervisors that are currently paused.
     *
     * @return int
     */
    protected function totalPausedMasters()
    {
        if (! $masters = app(MasterSupervisorRepository::class)->all()) {
            return 0;
        }

        return collect($masters)
            ->filter(fn ($master) => $master->status === 'paused')
            ->count();
    }
}
