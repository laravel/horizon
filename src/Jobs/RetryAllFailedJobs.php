<?php

namespace Laravel\Horizon\Jobs;

use Laravel\Horizon\JobId;
use Laravel\Horizon\Contracts\JobRepository;
use Illuminate\Contracts\Queue\Factory as Queue;

class RetryAllFailedJobs
{
    /**
     * Execute the job.
     *
     * @param  \Illuminate\Contracts\Queue\Factory $queue
     * @param  \Laravel\Horizon\Contracts\JobRepository $repository
     * @return void
     */
    public function handle(Queue $queue, JobRepository $repository)
    {
        $snapshotId = $repository->snapshotFailedJobs();

        $lastIndex = -1;
        while (true) {
            [$jobs, $count] = $this->getFailedJobsPage($repository, $snapshotId, $lastIndex);

            if ($jobs->isEmpty()) {
                break;
            }

            $this->pushJobsOnQueue($queue, $repository, $jobs);
            $lastIndex += $count;
        }

        $repository->deleteFailedJobsSnapshot($snapshotId);
    }

    /**
     * Gets a page of the failed jobs after a certain index.
     *
     * @param  \Laravel\Horizon\Contracts\JobRepository $repository
     * @param  string $snapshotId
     * @param  int $lastIndex
     * @return array
     */
    protected function getFailedJobsPage(JobRepository $repository, string $snapshotId, int $lastIndex)
    {
        $failedJobs = $repository->getJobsByType($snapshotId, $lastIndex);
        $count = $failedJobs->count();

        $failedJobs = $failedJobs->reject(function ($job) {
            return $this->jobHasNonFailedRetries($job) || $this->jobIsRetry($job);
        })->map(function ($job) {
            return [
                'id'         => $job->id,
                'queue'      => $job->queue,
                'payload'    => $job->payload,
                'connection' => $job->connection,
            ];
        });

        return [$failedJobs, $count];
    }

    /**
     * Whether or not a certain job has associated retries with pending or completed status.
     *
     * @param  object $job
     * @return bool
     */
    protected function jobHasNonFailedRetries($job)
    {
        return collect(json_decode($job->retried_by ?? '[]'))
            ->pluck('status')
            ->reject(function ($status) {
                return $status === 'failed';
            })->isNotEmpty();
    }

    /**
     * Whether or not the job is a retry of another job.
     *
     * @param  object $job
     * @return bool
     */
    protected function jobIsRetry($job)
    {
        $payload = json_decode($job->payload ?? '{}');

        return ! empty($payload->retry_of);
    }

    /**
     * Push list of jobs on queue.
     *
     * @param  \Illuminate\Contracts\Queue\Factory $queue
     * @param  \Laravel\Horizon\Contracts\JobRepository $repository
     * @param  $jobs
     */
    private function pushJobsOnQueue(Queue $queue, JobRepository $repository, $jobs)
    {
        foreach ($jobs as $job) {
            $payload = $this->preparePayload(
                $id = JobId::generate(),
                $job['id'],
                $job['payload']
            );

            $queue->connection($job['connection'])->pushRaw($payload, $job['queue']);

            $repository->storeRetryReference($job['id'], $id);
        }
    }

    /**
     * Prepare the payload for queueing.
     *
     * @param  string $id
     * @param  string $retryOf
     * @param  string $payload
     * @return string
     */
    protected function preparePayload($id, $retryOf, $payload): string
    {
        return json_encode(array_merge(json_decode($payload, true), [
            'id'       => $id,
            'attempts' => 0,
            'retry_of' => $retryOf,
        ]));
    }
}
