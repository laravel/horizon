<?php

namespace Laravel\Horizon\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\JobPayload;

class DatabaseJobRepository implements JobRepository
{
    /**
     * The database connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    public $resolver;

    /**
     * The columns selected when reading jobs.
     *
     * @var array
     */
    public $keys = [
        'id', 'connection', 'queue', 'name', 'status', 'payload',
        'exception', 'context', 'failed_at', 'completed_at', 'retried_by',
        'reserved_at', 'delay', 'monitored',
    ];

    /**
     * The number of minutes until recently failed jobs should be purged.
     *
     * @var int
     */
    public $recentFailedJobExpires;

    /**
     * The number of minutes until recent jobs should be purged.
     *
     * @var int
     */
    public $recentJobExpires;

    /**
     * The number of minutes until pending jobs should be purged.
     *
     * @var int
     */
    public $pendingJobExpires;

    /**
     * The number of minutes until completed and silenced jobs should be purged.
     *
     * @var int
     */
    public $completedJobExpires;

    /**
     * The number of minutes until failed jobs should be purged.
     *
     * @var int
     */
    public $failedJobExpires;

    /**
     * The number of minutes until monitored jobs should be purged.
     *
     * @var int
     */
    public $monitoredJobExpires;

    /**
     * Create a new repository instance.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @return void
     */
    public function __construct(ConnectionResolverInterface $resolver)
    {
        $this->resolver = $resolver;
        $this->recentJobExpires = config('horizon.trim.recent', 60);
        $this->pendingJobExpires = config('horizon.trim.pending', 60);
        $this->completedJobExpires = config('horizon.trim.completed', 60);
        $this->failedJobExpires = config('horizon.trim.failed', 10080);
        $this->recentFailedJobExpires = config('horizon.trim.recent_failed', $this->failedJobExpires);
        $this->monitoredJobExpires = config('horizon.trim.monitored', 10080);
    }

    /**
     * Get the next job ID that should be assigned.
     *
     * @return string
     */
    public function nextJobId()
    {
        return (string) Str::uuid();
    }

    /**
     * Get the total count of recent jobs.
     *
     * @return int
     */
    public function totalRecent()
    {
        return $this->countRecent();
    }

    /**
     * Get the total count of failed jobs.
     *
     * @return int
     */
    public function totalFailed()
    {
        return $this->table()->where('status', 'failed')->count();
    }

    /**
     * Get a chunk of recent jobs.
     *
     * @param  string|null  $afterIndex
     * @return \Illuminate\Support\Collection
     */
    public function getRecent($afterIndex = null)
    {
        return $this->getJobsByQuery(
            $this->table()->where('created_at', '>=', $this->cutoffTime($this->recentJobExpires)),
            $afterIndex
        );
    }

    /**
     * Get a chunk of failed jobs.
     *
     * @param  string|null  $afterIndex
     * @return \Illuminate\Support\Collection
     */
    public function getFailed($afterIndex = null)
    {
        return $this->getJobsByQuery(
            $this->table()
                ->where('status', 'failed')
                ->where('failed_at', '>=', $this->cutoffTime($this->failedJobExpires)),
            $afterIndex,
            'failed_at'
        );
    }

    /**
     * Get a chunk of pending jobs.
     *
     * @param  string|null  $afterIndex
     * @return \Illuminate\Support\Collection
     */
    public function getPending($afterIndex = null)
    {
        return $this->getJobsByQuery(
            $this->table()
                ->whereIn('status', ['pending', 'reserved'])
                ->where('created_at', '>=', $this->cutoffTime($this->pendingJobExpires)),
            $afterIndex
        );
    }

    /**
     * Get a chunk of completed jobs.
     *
     * @param  string|null  $afterIndex
     * @return \Illuminate\Support\Collection
     */
    public function getCompleted($afterIndex = null)
    {
        return $this->getJobsByQuery(
            $this->table()
                ->where('status', 'completed')
                ->where('completed_at', '>=', $this->cutoffTime($this->completedJobExpires)),
            $afterIndex,
            'completed_at'
        );
    }

    /**
     * Get a chunk of silenced jobs.
     *
     * @param  string|null  $afterIndex
     * @return \Illuminate\Support\Collection
     */
    public function getSilenced($afterIndex = null)
    {
        return $this->getJobsByQuery(
            $this->table()
                ->where('status', 'silenced')
                ->where('completed_at', '>=', $this->cutoffTime($this->completedJobExpires)),
            $afterIndex,
            'completed_at'
        );
    }

    /**
     * Get the count of recent jobs.
     *
     * @return int
     */
    public function countRecent()
    {
        return $this->table()
            ->where('created_at', '>=', $this->cutoffTime($this->recentJobExpires))
            ->count();
    }

    /**
     * Get the count of failed jobs.
     *
     * @return int
     */
    public function countFailed()
    {
        return $this->table()
            ->where('status', 'failed')
            ->where('failed_at', '>=', $this->cutoffTime($this->failedJobExpires))
            ->count();
    }

    /**
     * Get the count of pending jobs.
     *
     * @return int
     */
    public function countPending()
    {
        return $this->table()
            ->whereIn('status', ['pending', 'reserved'])
            ->where('created_at', '>=', $this->cutoffTime($this->pendingJobExpires))
            ->count();
    }

    /**
     * Get the count of completed jobs.
     *
     * @return int
     */
    public function countCompleted()
    {
        return $this->table()
            ->where('status', 'completed')
            ->where('completed_at', '>=', $this->cutoffTime($this->completedJobExpires))
            ->count();
    }

    /**
     * Get the count of silenced jobs.
     *
     * @return int
     */
    public function countSilenced()
    {
        return $this->table()
            ->where('status', 'silenced')
            ->where('completed_at', '>=', $this->cutoffTime($this->completedJobExpires))
            ->count();
    }

    /**
     * Get the count of the recently failed jobs.
     *
     * @return int
     */
    public function countRecentlyFailed()
    {
        return $this->table()
            ->where('status', 'failed')
            ->where('failed_at', '>=', $this->cutoffTime($this->recentFailedJobExpires))
            ->count();
    }

    /**
     * Get the cutoff timestamp for the given number of minutes.
     *
     * @param  int  $minutes
     * @return float
     */
    protected function cutoffTime($minutes)
    {
        return CarbonImmutable::now()->subMinutes($minutes)->getTimestamp();
    }

    /**
     * Get a chunk of jobs from the given query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string|null  $afterIndex
     * @param  string  $orderBy
     * @return \Illuminate\Support\Collection
     */
    protected function getJobsByQuery($query, $afterIndex, $orderBy = 'created_at')
    {
        $afterIndex = $afterIndex === null ? -1 : (int) $afterIndex;

        $records = $query->orderBy($orderBy, 'desc')
            ->orderBy('id', 'desc')
            ->offset($afterIndex + 1)
            ->limit(50)
            ->get();

        return $this->indexJobs(collect($records)->map(function ($record) {
            return $this->fromRecord($record);
        }), $afterIndex + 1);
    }

    /**
     * Retrieve the jobs with the given IDs.
     *
     * @param  array  $ids
     * @param  mixed  $indexFrom
     * @return \Illuminate\Support\Collection
     */
    public function getJobs(array $ids, $indexFrom = 0)
    {
        if (empty($ids)) {
            return collect();
        }

        $records = $this->table()
            ->whereIn('id', array_map('strval', $ids))
            ->get()
            ->keyBy('id');

        $jobs = collect($ids)->map(function ($id) use ($records) {
            return isset($records[(string) $id]) ? $this->fromRecord($records[(string) $id]) : null;
        })->filter()->values();

        return $this->indexJobs($jobs, $indexFrom);
    }

    /**
     * Convert a database record into a stdClass job object.
     *
     * @param  object  $record
     * @return \stdClass
     */
    protected function fromRecord($record)
    {
        $job = new \stdClass;

        foreach ($this->keys as $key) {
            $job->{$key} = $record->{$key} ?? null;
        }

        return $job;
    }

    /**
     * Index the given jobs from the given index.
     *
     * @param  \Illuminate\Support\Collection  $jobs
     * @param  int  $indexFrom
     * @return \Illuminate\Support\Collection
     */
    protected function indexJobs($jobs, $indexFrom)
    {
        return $jobs->values()->map(function ($job) use (&$indexFrom) {
            $job->index = $indexFrom;

            $indexFrom++;

            return $job;
        });
    }

    /**
     * Insert the job into storage.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  \Laravel\Horizon\JobPayload  $payload
     * @return void
     */
    public function pushed($connection, $queue, JobPayload $payload)
    {
        $time = $this->microtime();

        $this->table()->upsert([
            'id' => $payload->id(),
            'connection' => $connection,
            'queue' => $queue,
            'name' => $payload->decoded['displayName'],
            'status' => 'pending',
            'payload' => $payload->value,
            'created_at' => $time,
            'updated_at' => $time,
            'monitored' => false,
        ], ['id'], [
            'connection', 'queue', 'name', 'status', 'payload', 'updated_at',
        ]);
    }

    /**
     * Mark the job as reserved.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  \Laravel\Horizon\JobPayload  $payload
     * @return void
     */
    public function reserved($connection, $queue, JobPayload $payload)
    {
        $time = $this->microtime();

        $this->table()->where('id', $payload->id())->update([
            'status' => 'reserved',
            'payload' => $payload->value,
            'updated_at' => $time,
            'reserved_at' => $time,
        ]);
    }

    /**
     * Mark the job as released / pending.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  \Laravel\Horizon\JobPayload  $payload
     * @param  int  $delay
     * @return void
     */
    public function released($connection, $queue, JobPayload $payload, $delay = 0)
    {
        $this->table()->where('id', $payload->id())->update([
            'status' => 'pending',
            'payload' => $payload->value,
            'updated_at' => $this->microtime(),
            'delay' => $delay,
        ]);
    }

    /**
     * Mark the job as completed and monitored.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  \Laravel\Horizon\JobPayload  $payload
     * @return void
     */
    public function remember($connection, $queue, JobPayload $payload)
    {
        $time = $this->microtime();

        $this->table()->upsert([
            'id' => $payload->id(),
            'connection' => $connection,
            'queue' => $queue,
            'name' => $payload->decoded['displayName'],
            'status' => 'completed',
            'payload' => $payload->value,
            'completed_at' => $time,
            'created_at' => $time,
            'updated_at' => $time,
            'monitored' => true,
        ], ['id'], [
            'connection', 'queue', 'name', 'status', 'payload',
            'completed_at', 'updated_at', 'monitored',
        ]);
    }

    /**
     * Mark the given jobs as released / pending.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  \Illuminate\Support\Collection  $payloads
     * @return void
     */
    public function migrated($connection, $queue, Collection $payloads)
    {
        $time = $this->microtime();

        foreach ($payloads as $payload) {
            $this->table()->where('id', $payload->id())->update([
                'status' => 'pending',
                'payload' => $payload->value,
                'updated_at' => $time,
            ]);
        }
    }

    /**
     * Handle the storage of a completed job.
     *
     * @param  \Laravel\Horizon\JobPayload  $payload
     * @param  bool  $failed
     * @param  bool  $silenced
     * @return void
     */
    public function completed(JobPayload $payload, $failed = false, $silenced = false)
    {
        if ($payload->isRetry()) {
            $this->updateRetryInformationOnParent($payload, $failed);
        }

        if ($failed) {
            return;
        }

        $this->table()->where('id', $payload->id())->update([
            'status' => $silenced ? 'silenced' : 'completed',
            'completed_at' => $this->microtime(),
        ]);
    }

    /**
     * Update the retry status of a job's parent.
     *
     * @param  \Laravel\Horizon\JobPayload  $payload
     * @param  bool  $failed
     * @return void
     */
    protected function updateRetryInformationOnParent(JobPayload $payload, $failed)
    {
        if ($retries = $this->table()->where('id', $payload->retryOf())->value('retried_by')) {
            $retries = $this->updateRetryStatus(
                $payload, json_decode($retries, true), $failed
            );

            $this->table()->where('id', $payload->retryOf())->update([
                'retried_by' => json_encode($retries),
            ]);
        }
    }

    /**
     * Update the retry status of a job in a retry array.
     *
     * @param  \Laravel\Horizon\JobPayload  $payload
     * @param  array  $retries
     * @param  bool  $failed
     * @return array
     */
    protected function updateRetryStatus(JobPayload $payload, $retries, $failed)
    {
        return collect($retries)->map(function ($retry) use ($payload, $failed) {
            return $retry['id'] === $payload->id()
                    ? Arr::set($retry, 'status', $failed ? 'failed' : 'completed')
                    : $retry;
        })->all();
    }

    /**
     * Delete the given monitored jobs by IDs.
     *
     * @param  array  $ids
     * @return void
     */
    public function deleteMonitored(array $ids)
    {
        $this->table()
            ->whereIn('id', array_map('strval', $ids))
            ->where('monitored', true)
            ->delete();
    }

    /**
     * Trim the recent job list.
     *
     * @return void
     */
    public function trimRecentJobs()
    {
        $this->table()
            ->where('status', '!=', 'failed')
            ->where('monitored', false)
            ->where('created_at', '<', $this->cutoffTime($this->recentJobExpires))
            ->delete();
    }

    /**
     * Trim the failed job list.
     *
     * @return void
     */
    public function trimFailedJobs()
    {
        $this->table()
            ->where('status', 'failed')
            ->where('failed_at', '<', $this->cutoffTime($this->failedJobExpires))
            ->delete();
    }

    /**
     * Trim the monitored job list.
     *
     * @return void
     */
    public function trimMonitoredJobs()
    {
        $this->table()
            ->where('monitored', true)
            ->where('completed_at', '<', $this->cutoffTime($this->monitoredJobExpires))
            ->delete();
    }

    /**
     * Find a failed job by ID.
     *
     * @param  string  $id
     * @return \stdClass|null
     */
    public function findFailed($id)
    {
        $record = $this->table()->where('id', (string) $id)->first();

        if (! $record || $record->status !== 'failed') {
            return;
        }

        return $this->fromRecord($record);
    }

    /**
     * Mark the job as failed.
     *
     * @param  \Throwable  $exception
     * @param  string  $connection
     * @param  string  $queue
     * @param  \Laravel\Horizon\JobPayload  $payload
     * @return void
     */
    public function failed($exception, $connection, $queue, JobPayload $payload)
    {
        $time = $this->microtime();

        $this->table()->upsert([
            'id' => $payload->id(),
            'connection' => $connection,
            'queue' => $queue,
            'name' => $payload->decoded['displayName'],
            'status' => 'failed',
            'payload' => $payload->value,
            'exception' => (string) $exception,
            'context' => method_exists($exception, 'context')
                ? json_encode($exception->context())
                : null,
            'failed_at' => $time,
            'created_at' => $time,
            'updated_at' => $time,
        ], ['id'], [
            'connection', 'queue', 'name', 'status', 'payload',
            'exception', 'context', 'failed_at', 'updated_at',
        ]);
    }

    /**
     * Store the retry job ID on the original job record.
     *
     * @param  string  $id
     * @param  string  $retryId
     * @return void
     */
    public function storeRetryReference($id, $retryId)
    {
        $retries = json_decode(
            $this->table()->where('id', $id)->value('retried_by') ?: '[]', true
        );

        $retries[] = [
            'id' => $retryId,
            'status' => 'pending',
            'retried_at' => CarbonImmutable::now()->getTimestamp(),
        ];

        $this->table()->where('id', $id)->update([
            'retried_by' => json_encode($retries),
        ]);
    }

    /**
     * Delete a failed job by ID.
     *
     * @param  string  $id
     * @return int
     */
    public function deleteFailed($id)
    {
        return $this->table()
            ->where('id', (string) $id)
            ->where('status', 'failed')
            ->delete();
    }

    /**
     * Delete pending and reserved jobs for a queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function purge($queue)
    {
        return $this->table()
            ->where('queue', $queue)
            ->whereIn('status', ['pending', 'reserved'])
            ->delete();
    }

    /**
     * Get the current microtime as a string with microsecond precision.
     *
     * @return string
     */
    protected function microtime()
    {
        return str_replace(',', '.', microtime(true));
    }

    /**
     * Get a query builder for the horizon jobs table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        return $this->connection()->table('horizon_jobs');
    }

    /**
     * Get the database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection()
    {
        return $this->resolver->connection(config('horizon.database.connection'));
    }
}
