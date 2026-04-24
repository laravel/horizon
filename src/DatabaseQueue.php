<?php

namespace Laravel\Horizon;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\DatabaseQueue as BaseQueue;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Events\JobDeleted;
use Laravel\Horizon\Events\JobPending;
use Laravel\Horizon\Events\JobPushed;
use Laravel\Horizon\Events\JobReleased;
use Laravel\Horizon\Events\JobReserved;
use Laravel\Horizon\Jobs\HorizonDatabaseJob;

class DatabaseQueue extends BaseQueue
{
    /**
     * The job that last pushed to queue via the "push" method.
     *
     * @var object|string
     */
    protected $lastPushed;

    /**
     * Get the number of queue jobs that are ready to process.
     *
     * @param  string|null  $queue
     * @return int
     */
    public function readyNow($queue = null)
    {
        return $this->pendingSize($queue);
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    #[\Override]
    public function push($job, $data = '', $queue = null)
    {
        return $this->enqueueUsing(
            $job,
            $this->createPayload($job, $this->getQueue($queue), $data),
            $queue,
            null,
            function ($payload, $queue) use ($job) {
                $this->lastPushed = $job;

                return $this->pushRaw($payload, $queue);
            }
        );
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string|null  $queue
     * @param  array  $options
     * @return mixed
     */
    #[\Override]
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $payload = (new JobPayload($payload))->prepare($this->lastPushed);

        $this->event($this->getQueue($queue), new JobPending($payload->value));

        $this->pushToDatabase($queue, $payload->value);

        $this->event($this->getQueue($queue), new JobPushed($payload->value));

        return $payload->id();
    }

    /**
     * Create a payload array from the given job and data.
     *
     * @param  string  $job
     * @param  string  $queue
     * @param  mixed  $data
     * @return array
     */
    #[\Override]
    protected function createPayloadArray($job, $queue, $data = '')
    {
        $payload = parent::createPayloadArray($job, $queue, $data);

        $payload['id'] = $payload['uuid'];

        return $payload;
    }

    /**
     * Push a new job onto the queue after (n) seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    #[\Override]
    public function later($delay, $job, $data = '', $queue = null)
    {
        $payload = (new JobPayload(
            $this->createPayload($job, $this->getQueue($queue), $data, $delay)
        ))->prepare($job);

        return $this->enqueueUsing(
            $job,
            $payload->value,
            $queue,
            $delay,
            function ($payload, $queue, $delay) {
                $this->event($this->getQueue($queue), new JobPending($payload));

                $this->pushToDatabase($queue, $payload, $delay);

                $this->event($this->getQueue($queue), new JobPushed($payload));

                return (new JobPayload($payload))->id();
            }
        );
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string|null  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     *
     * @throws \Throwable
     */
    #[\Override]
    public function pop($queue = null)
    {
        return tap(parent::pop($queue), function ($result) use ($queue) {
            if ($result instanceof HorizonDatabaseJob) {
                $this->event(
                    $this->getQueue($queue),
                    new JobReserved($result->getJobRecord()->payload)
                );
            }
        });
    }

    /**
     * Marshal the reserved job into a HorizonDatabaseJob instance.
     *
     * @param  string  $queue
     * @param  \Illuminate\Queue\Jobs\DatabaseJobRecord  $job
     * @return \Laravel\Horizon\Jobs\HorizonDatabaseJob
     */
    #[\Override]
    protected function marshalJob($queue, $job)
    {
        return new HorizonDatabaseJob(
            $this->container,
            $this,
            $this->markJobAsReserved($job),
            $this->connectionName,
            $queue,
        );
    }

    /**
     * Delete a reserved job from the reserved queue and release it.
     *
     * @param  string  $queue
     * @param  \Laravel\Horizon\Jobs\HorizonDatabaseJob  $job
     * @param  int  $delay
     * @return void
     */
    #[\Override]
    public function deleteAndRelease($queue, $job, $delay)
    {
        parent::deleteAndRelease($queue, $job, $delay);

        $this->event(
            $this->getQueue($queue),
            new JobReleased($job->getJobRecord()->payload, $delay)
        );
    }

    /**
     * Delete all of the jobs from the queue and purge Horizon's pending metadata.
     *
     * @param  string  $queue
     * @return int
     */
    #[\Override]
    public function clear($queue)
    {
        $deleted = parent::clear($queue);

        $this->container->make(JobRepository::class)->purge($this->getQueue($queue));

        return $deleted;
    }

    /**
     * Fire the "job deleted" event for the given job.
     *
     * @param  string  $queue
     * @param  \Laravel\Horizon\Jobs\HorizonDatabaseJob  $job
     * @return void
     */
    public function fireJobDeletedEvent($queue, $job)
    {
        $this->event(
            $this->getQueue($queue),
            new JobDeleted($job, $job->getJobRecord()->payload)
        );
    }

    /**
     * Fire the given event if a dispatcher is bound.
     *
     * @param  string  $queue
     * @param  mixed  $event
     * @return void
     */
    protected function event($queue, $event)
    {
        if ($this->container && $this->container->bound(Dispatcher::class)) {
            $this->container->make(Dispatcher::class)->dispatch(
                $event->connection($this->getConnectionName())->queue($queue)
            );
        }
    }
}
