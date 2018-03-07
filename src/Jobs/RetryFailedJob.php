<?php

namespace Laravel\Horizon\Jobs;

use Illuminate\Contracts\Queue\Factory as Queue;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\JobId;
use ReflectionClass;
use ReflectionException;

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

        $overwrite = $this->updateTimeoutAt($payload, [
            'id' => $id,
            'attempts' => 0,
            'retry_of' => $this->id,
        ]);

        return json_encode(array_merge($payload, $overwrite));
    }

    /**
     * Refresh the timeoutAt of the payload if exists
     *
     * @param array  $payload
     * @param array  $overwrite
     *
     * @return array
     */
    protected function updateTimeoutAt(array $payload, array $overwrite)
    {
        if ($payload['timeoutAt'] < time()) {
            try {
                if ((new ReflectionClass($payload['data']['commandName']))->hasMethod('retryUntil')) {
                    $overwrite['timeoutAt'] = (unserialize($payload['data']['command']))->retryUntil();
                }
            } catch (ReflectionException $e) {
            }
        }

        return $overwrite;
    }
}