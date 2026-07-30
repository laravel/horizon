<?php

declare(strict_types=1);

namespace Laravel\Horizon\Batches;

use Illuminate\Bus\Batch;
use Laravel\Horizon\Contracts\JobRepository;

/**
 * Present batch counters and failed-job lineages for the Horizon UI.
 *
 * Does not mutate stored batch counters. Exposes unfinished failures as failedJobs
 * (min of pending and raw failed_jobs), the still-runnable remainder as pendingJobs,
 * and the raw failed_jobs counter as failedJobAttempts.
 */
final readonly class BatchPresentation
{
    public function __construct(
        private JobRepository $jobs,
        private BatchFailedJobLineages $lineages,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(Batch $batch): array
    {
        $data = $batch->toArray();
        $rawFailed = max(0, (int) $batch->failedJobs);
        $failedJobs = min(max(0, (int) $batch->pendingJobs), $rawFailed);
        $pendingJobs = max(0, (int) $batch->pendingJobs - $failedJobs);

        $data['failedJobs'] = $failedJobs;
        $data['failedJobAttempts'] = $rawFailed;
        $data['pendingJobs'] = $pendingJobs;

        return $data;
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, complete: bool}
     */
    public function failedJobs(Batch $batch): array
    {
        $logicalFailed = min(max(0, (int) $batch->pendingJobs), max(0, (int) $batch->failedJobs));

        if ($logicalFailed === 0 || $batch->failedJobIds === []) {
            return [
                'rows' => [],
                'complete' => true,
            ];
        }

        $ids = array_values(array_unique(array_filter(
            $batch->failedJobIds,
            static fn (mixed $id): bool => is_string($id) && trim($id) !== '',
        )));

        if ($ids === []) {
            return [
                'rows' => [],
                'complete' => false,
            ];
        }

        $retainedJobs = $this->jobs->getJobs($ids)->all();
        $summary = $this->lineages->summarize($retainedJobs, $ids);
        $rows = array_slice($summary['rows'], 0, $logicalFailed);
        $complete = $summary['complete'] && count($rows) >= $logicalFailed;

        return [
            'rows' => $rows,
            'complete' => $complete,
        ];
    }
}
