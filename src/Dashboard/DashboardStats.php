<?php

declare(strict_types=1);

namespace Laravel\Horizon\Dashboard;

use Laravel\Horizon\Batches\BatchRepositoryOverview;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\WaitTimeCalculator;
use Throwable;

final readonly class DashboardStats
{
    public function __construct(
        private JobRepository $jobs,
        private MetricsRepository $metrics,
        private SupervisorRepository $supervisors,
        private WaitTimeCalculator $waitTimes,
        private BatchRepositoryOverview $batches,
        private HorizonStatus $status,
        private PendingJobCounts $pendingJobs,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        $waits = collect($this->waitTimes->calculate());
        $pending = $this->pendingJobs->get($waits->all());
        $batches = $this->batches->get();
        $queueWithMaxRuntime = $this->metrics->queueWithMaximumRuntime();
        $queueWithMaxThroughput = $this->metrics->queueWithMaximumThroughput();
        $recentJobs = $this->jobs->countRecent();

        return [
            'activeBatches' => $batches['active'],
            'batchPreviews' => $batches['previews'],
            'failedJobs' => $this->jobs->countRecentlyFailed(),
            'failedJobsPastDay' => $this->failedJobsPastDay(),
            'failedJobsPastHour' => $this->failedJobsPastHour(),
            'hourlyPressure' => $this->hourlyPressure($recentJobs),
            'jobsPerMinute' => $this->metrics->jobsProcessedPerMinute(),
            'maxRuntime' => $this->runtimeInSecondsForQueue($queueWithMaxRuntime),
            'maxThroughput' => $this->throughputForQueue($queueWithMaxThroughput),
            'pendingDelayed' => $pending['delayed'],
            'pendingJobs' => $pending['total'],
            'pendingReadyNow' => $pending['ready'],
            'pendingReserved' => $pending['reserved'],
            'pausedMasters' => $this->status->pausedMasters(),
            'periods' => [
                'failedJobs' => config('horizon.trim.recent_failed', config('horizon.trim.failed')),
                'recentJobs' => config('horizon.trim.recent'),
                'completedJobs' => config('horizon.trim.completed', config('horizon.trim.recent')),
            ],
            'processes' => $this->totalProcessCount(),
            'queueWithMaxRuntime' => $queueWithMaxRuntime,
            'queueWithMaxThroughput' => $queueWithMaxThroughput,
            'recentJobs' => $recentJobs,
            'status' => $this->status->current(),
            'throughput' => $this->metrics->throughput(),
            'wait' => $waits->sortDesc()->take(1),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function legacy(): array
    {
        return [
            'failedJobs' => $this->jobs->countRecentlyFailed(),
            'jobsPerMinute' => $this->metrics->jobsProcessedPerMinute(),
            'pausedMasters' => $this->status->pausedMasters(),
            'periods' => [
                'failedJobs' => config('horizon.trim.recent_failed', config('horizon.trim.failed')),
                'recentJobs' => config('horizon.trim.recent'),
            ],
            'processes' => $this->totalProcessCount(),
            'queueWithMaxRuntime' => $this->metrics->queueWithMaximumRuntime(),
            'queueWithMaxThroughput' => $this->metrics->queueWithMaximumThroughput(),
            'recentJobs' => $this->jobs->countRecent(),
            'status' => $this->status->legacy(),
            'wait' => collect($this->waitTimes->calculate())->take(1),
        ];
    }

    private function totalProcessCount(): int
    {
        return collect($this->supervisors->all())
            ->reduce(
                fn (int $total, object $supervisor): int => $total + collect($supervisor->processes)->sum(),
                0,
            );
    }

    /**
     * Jobs received by Horizon during the past hour, or null when unavailable.
     *
     * When recent job retention is exactly 60 minutes, the precomputed
     * countRecent() value is already the hour window and works for custom
     * JobRepository implementations. When retention is longer, countRecentSince(60)
     * is required. When retention is shorter than an hour, a full hour cannot
     * be reconstructed.
     */
    private function hourlyPressure(int $recentJobs): ?int
    {
        $trim = (int) config('horizon.trim.recent', 60);

        if ($trim < 60) {
            return null;
        }

        if ($trim === 60) {
            return $recentJobs;
        }

        if (! method_exists($this->jobs, 'countRecentSince')) {
            return null;
        }

        return $this->jobs->countRecentSince(60);
    }

    /**
     * Failed jobs recorded during the past hour, or null when unavailable.
     */
    private function failedJobsPastHour(): ?int
    {
        if (config('horizon.trim.failed', 10080) < 60 || ! method_exists($this->jobs, 'countFailedSince')) {
            return null;
        }

        return $this->jobs->countFailedSince(60);
    }

    /**
     * Failed jobs recorded during the past 24 hours, or null when unavailable.
     */
    private function failedJobsPastDay(): ?int
    {
        if (config('horizon.trim.failed', 10080) < 1440 || ! method_exists($this->jobs, 'countFailedSince')) {
            return null;
        }

        return $this->jobs->countFailedSince(1440);
    }

    /**
     * Average runtime for a queue leader in seconds, or null when unavailable.
     * Metric runtimes are stored in milliseconds; conversion matches metrics chart snapshots.
     */
    private function runtimeInSecondsForQueue(mixed $queue): ?float
    {
        if (! is_string($queue) || $queue === '') {
            return null;
        }

        try {
            $runtime = $this->metrics->runtimeForQueue($queue);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }

        if (! is_numeric($runtime)) {
            return null;
        }

        return round((float) $runtime / 1000, 3);
    }

    /**
     * Throughput for a queue leader since the last snapshot, or null when unavailable.
     */
    private function throughputForQueue(mixed $queue): ?int
    {
        if (! is_string($queue) || $queue === '') {
            return null;
        }

        try {
            $throughput = $this->metrics->throughputForQueue($queue);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }

        if (! is_numeric($throughput)) {
            return null;
        }

        return (int) $throughput;
    }
}
