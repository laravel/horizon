<?php

namespace Laravel\Horizon\Jobs;

use Cake\Chronos\Chronos;
use Illuminate\Contracts\Queue\Factory as Queue;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\JobId;

class RetryFailedJob
{
    /**
     * The job ID.
     *
     * @var string
     */
    public $id;

    /**
     * Create a new job instance.
     *
     * @param  string  $id
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
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
            $this->preparePayload($id = JobId::generate(), $job->payload), $job->queue
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
            'attempts' => 0,
            'retry_of' => $this->id,
            'timeoutAt' => $this->prepareNewTimeout($payload),
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
        return $payload['timeoutAt']
                        ? Chronos::now()->addSeconds(ceil($payload['timeoutAt'] - $payload['pushedAt']))->getTimestamp()
                        : null;
    }
}
