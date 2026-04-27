<?php

namespace Laravel\Horizon\Jobs;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\Factory as Queue;
use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\JobRepository;

class RetryFailedJob
{
    /**
     * Create a new job instance.
     *
     * @param  string  $id  The job ID.
     * @return void
     */
    public function __construct(
        public $id,
    ) {
    }

    /**
     * Execute the job.
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @param  \Laravel\Horizon\Contracts\JobRepository  $jobs
     * @return void
     */
    public function handle(Queue $queue, JobRepository $jobs)
    {
        if (is_null($job = $jobs->findFailed($this->id))) {
            return;
        }

        $queue->connection($job->connection)->pushRaw(
            $this->preparePayload($id = Str::uuid(), $job->payload), $job->queue
        );

        $jobs->storeRetryReference($this->id, $id);
    }

    /**
     * Prepare the payload for queueing.
     *
     * @param  string  $id
     * @param  string  $payload
     * @return string
     */
    protected function preparePayload($id, $payload)
    {
        $payload = json_decode($payload, true);

        return json_encode(array_merge($payload, [
            'id' => $id,
            'uuid' => $id,
            'attempts' => 0,
            'retry_of' => $this->id,
            'retryUntil' => $this->prepareNewTimeout($payload),
        ]));
    }

    /**
     * Prepare the timeout.
     *
     * @param  array  $payload
     * @return int|null
     */
    protected function prepareNewTimeout($payload)
    {
        $retryUntil = $payload['retryUntil'] ?? $payload['timeoutAt'] ?? null;

        $pushedAt = $payload['pushedAt'] ?? microtime(true);

        return $retryUntil
            ? CarbonImmutable::now()->addSeconds(ceil($retryUntil - $pushedAt))->getTimestamp()
            : null;
    }
}
