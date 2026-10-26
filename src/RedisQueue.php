<?php

namespace Laravel\Horizon;

use Illuminate\Bus\UniqueLock;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Foundation\Application;
use Illuminate\Queue\RedisQueue as BaseQueue;
use Illuminate\Support\Str;
use Laravel\Horizon\Events\JobDeleted;
use Laravel\Horizon\Events\JobPending;
use Laravel\Horizon\Events\JobPushed;
use Laravel\Horizon\Events\JobReleased;
use Laravel\Horizon\Events\JobReserved;
use Laravel\Horizon\Events\JobsMigrated;
use Throwable;

class RedisQueue extends BaseQueue
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
        $key = method_exists(parent::class, 'getQueueRedisKey')
            ? $this->getQueueRedisKey($queue)
            : $this->getQueue($queue);

        return $this->getConnection()->llen($key);
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  object|string  $job
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

        parent::pushRaw($payload->value, $queue, $options);

        $this->event($this->getQueue($queue), new JobPushed($payload->value));

        return $payload->id();
    }

    /**
     * Create a payload string from the given job and data.
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
     * Push a new job onto the queue after a delay.
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
        $args = version_compare(Application::VERSION, '12.11.0', '>=')
            ? [$job, $queue, $data, $delay]
            : [$job, $queue, $data];

        $payload = (new JobPayload($this->createPayload(...$args)))->prepare($job)->value;

        if (method_exists($this, 'enqueueUsing')) {
            return $this->enqueueUsing(
                $job,
                $payload,
                $queue,
                $delay,
                function ($payload, $queue, $delay) {
                    $this->event($this->getQueue($queue), new JobPending($payload));

                    return tap(parent::laterRaw($delay, $payload, $queue), function () use ($payload, $queue) {
                        $this->event($this->getQueue($queue), new JobPushed($payload));
                    });
                }
            );
        }

        $this->event($this->getQueue($queue), new JobPending($payload));

        return tap(parent::laterRaw($delay, $payload, $queue), function () use ($payload, $queue) {
            $this->event($this->getQueue($queue), new JobPushed($payload));
        });
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string|null  $queue
     * @param  int  $index
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    #[\Override]
    public function pop($queue = null, $index = 0)
    {
        return tap(parent::pop($queue, $index), function ($result) use ($queue) {
            if ($result) {
                $this->event($this->getQueue($queue), new JobReserved($result->getReservedJob()));
            }
        });
    }

    /**
     * Migrate the delayed jobs that are ready to the regular queue.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    #[\Override]
    public function migrateExpiredJobs($from, $to)
    {
        return tap(parent::migrateExpiredJobs($from, $to), function ($jobs) use ($to) {
            $this->event($to, new JobsMigrated($jobs === false ? [] : $jobs));
        });
    }

    /**
     * Delete a reserved job from the queue.
     *
     * @param  string  $queue
     * @param  \Illuminate\Queue\Jobs\RedisJob  $job
     * @return void
     */
    #[\Override]
    public function deleteReserved($queue, $job)
    {
        parent::deleteReserved($queue, $job);

        $this->event($this->getQueue($queue), new JobDeleted($job, $job->getReservedJob()));
    }

    /**
     * Delete a reserved job from the reserved queue and release it.
     *
     * @param  string  $queue
     * @param  \Illuminate\Queue\Jobs\RedisJob  $job
     * @param  int  $delay
     * @return void
     */
    #[\Override]
    public function deleteAndRelease($queue, $job, $delay)
    {
        parent::deleteAndRelease($queue, $job, $delay);

        $this->event($this->getQueue($queue), new JobReleased($job->getReservedJob(), $delay));
    }

    /**
     * Delete all of the jobs from the queue.
     *
     * The unique locks of the removed jobs are released. Reserved jobs whose
     * reservation has not expired yet may still be running, so their locks
     * are left for the worker to release once the job has finished.
     *
     * @param  string|null  $queue
     * @return int
     */
    #[\Override]
    public function clear($queue = null)
    {
        $queue = method_exists(parent::class, 'getQueueRedisKey')
            ? $this->getQueueRedisKey($queue)
            : $this->getQueue($queue);

        [$size, $payloads] = $this->getConnection()->eval(
            LuaScripts::clear(), 4, $queue, $queue.':delayed', $queue.':reserved', $queue.':notify',
            is_null($this->retryAfter) ? '-inf' : $this->currentTime()
        );

        foreach ($payloads as $payload) {
            $this->releaseUniqueLock($payload);
        }

        return (int) $size;
    }

    /**
     * Release the unique lock held by the job in the given payload.
     *
     * @param  string  $payload
     * @return void
     */
    protected function releaseUniqueLock($payload)
    {
        $payload = json_decode($payload, true);

        $class = $payload['data']['commandName'] ?? null;

        if (! is_string($class) || ! is_a($class, ShouldBeUnique::class, true) ||
            ! is_string($payload['data']['command'] ?? null)) {
            return;
        }

        // Jobs that are unique until processing release their lock when they start...
        if (is_a($class, ShouldBeUniqueUntilProcessing::class, true) &&
            ($payload['attempts'] ?? 0) > 0) {
            return;
        }

        try {
            $command = str_starts_with($payload['data']['command'], 'O:')
                ? unserialize($payload['data']['command'])
                : unserialize($this->container[Encrypter::class]->decrypt($payload['data']['command']));
        } catch (Throwable) {
            return;
        }

        if ($command instanceof ShouldBeUnique && method_exists(UniqueLock::class, 'release')) {
            (new UniqueLock($this->container->make(Cache::class)))->release($command);
        }
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
            $queue = Str::replaceFirst('queues:', '', $queue);

            $this->container->make(Dispatcher::class)->dispatch(
                $event->connection($this->getConnectionName())->queue($queue)
            );
        }
    }
}
