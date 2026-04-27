<?php

namespace Laravel\Horizon;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\DatabaseQueue as BaseQueue;
use Laravel\Horizon\Events\JobPushed;
use Laravel\Horizon\Events\JobReserved;
use Laravel\Horizon\Jobs\DatabaseJob;

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
        $expiration = $this->currentTime() - $this->retryAfter;

        return $this->database->table($this->table)
                    ->where('queue', $this->getQueue($queue))
                    ->where(function ($query) use ($expiration) {
                        $query->where(function ($query) {
                            $query->whereNull('reserved_at')
                                ->where('available_at', '<=', $this->currentTime());
                        })->orWhere('reserved_at', '<=', $expiration);
                    })
                    ->count();
    }

    /**
     * Push an array of jobs onto the queue.
     *
     * @param  array  $jobs
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return void
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        foreach ((array) $jobs as $job) {
            $this->push($job, $data, $queue);
        }
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  object|string  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
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
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $payload = (new JobPayload($payload))->prepare($this->lastPushed);

        return tap(parent::pushRaw($payload->value, $queue, $options), function () use ($payload, $queue) {
            $this->event($this->getQueue($queue), new JobPushed($payload->value));
        });
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string  $job
     * @param  mixed  $data
     * @param  string  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        $payload = (new JobPayload($this->createPayload($job, $queue, $data)))->prepare($job)->value;

        return $this->enqueueUsing(
            $job,
            $payload,
            $queue,
            $delay,
            function ($payload, $queue, $delay) {
                return tap($this->pushToDatabase($queue, $payload, $delay), function () use ($payload, $queue) {
                    $this->event($this->getQueue($queue), new JobPushed($payload));
                });
            }
        );
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param  string  $job
     * @param  string  $queue
     * @param  mixed  $data
     * @return array
     */
    protected function createPayloadArray($job, $queue, $data = '')
    {
        $payload = parent::createPayloadArray($job, $queue, $data);

        $payload['id'] = $payload['uuid'];

        return $payload;
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string|null  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        return tap(parent::pop($queue), function ($result) use ($queue) {
            if ($result) {
                $this->event($this->getQueue($queue), new JobReserved($result->getRawBody()));
            }
        });
    }

    /**
     * Marshal the reserved job into a DatabaseJob instance.
     *
     * @param  string  $queue
     * @param  \Illuminate\Queue\Jobs\DatabaseJobRecord  $job
     * @return \Laravel\Horizon\Jobs\DatabaseJob
     */
    protected function marshalJob($queue, $job)
    {
        $job = $this->markJobAsReserved($job);

        return new DatabaseJob(
            $this->container, $this, $job, $this->connectionName, $queue
        );
    }

    /**
     * Fire the given event if a dispatcher is bound.
     *
     * @param  string  $queue
     * @param  mixed  $event
     * @return void
     */
    public function event($queue, $event)
    {
        if ($this->container && $this->container->bound(Dispatcher::class)) {
            $this->container->make(Dispatcher::class)->dispatch(
                $event->connection($this->getConnectionName())->queue($queue)
            );
        }
    }
}
