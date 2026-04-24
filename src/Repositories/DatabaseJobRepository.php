<?php

namespace Laravel\Horizon\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Enums\JobReferenceType;
use Laravel\Horizon\Enums\JobStatus;
use Laravel\Horizon\JobPayload;
use Laravel\Horizon\Models\HorizonJob;
use Laravel\Horizon\Models\HorizonJobReference;

class DatabaseJobRepository implements JobRepository
{
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
     */
    public function __construct()
    {
        $this->recentJobExpires = (int) config('horizon.trim.recent', 60);
        $this->pendingJobExpires = (int) config('horizon.trim.pending', 60);
        $this->completedJobExpires = (int) config('horizon.trim.completed', 60);
        $this->failedJobExpires = (int) config('horizon.trim.failed', 10080);
        $this->recentFailedJobExpires = (int) config('horizon.trim.recent_failed', $this->failedJobExpires);
        $this->monitoredJobExpires = (int) config('horizon.trim.monitored', 10080);
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
        return HorizonJobReference::where('type', JobReferenceType::Recent)->count();
    }

    /**
     * Get the total count of failed jobs.
     *
     * @return int
     */
    public function totalFailed()
    {
        return HorizonJobReference::where('type', JobReferenceType::Failed)->count();
    }

    /**
     * Get a chunk of recent jobs.
     *
     * @param  string|null  $afterIndex
     * @return \Illuminate\Support\Collection
     */
    public function getRecent($afterIndex = null)
    {
        return $this->getJobsByType(JobReferenceType::Recent, $afterIndex);
    }

    /**
     * Get a chunk of failed jobs.
     *
     * @param  string|null  $afterIndex
     * @return \Illuminate\Support\Collection
     */
    public function getFailed($afterIndex = null)
    {
        return $this->getJobsByType(JobReferenceType::Failed, $afterIndex);
    }

    /**
     * Get a chunk of pending jobs.
     *
     * @param  string|null  $afterIndex
     * @return \Illuminate\Support\Collection
     */
    public function getPending($afterIndex = null)
    {
        return $this->getJobsByType(JobReferenceType::Pending, $afterIndex);
    }

    /**
     * Get a chunk of completed jobs.
     *
     * @param  string|null  $afterIndex
     * @return \Illuminate\Support\Collection
     */
    public function getCompleted($afterIndex = null)
    {
        return $this->getJobsByType(JobReferenceType::Completed, $afterIndex);
    }

    /**
     * Get a chunk of silenced jobs.
     *
     * @param  string|null  $afterIndex
     * @return \Illuminate\Support\Collection
     */
    public function getSilenced($afterIndex = null)
    {
        return $this->getJobsByType(JobReferenceType::Silenced, $afterIndex);
    }

    /**
     * Get the count of recent jobs within the retention window.
     *
     * @return int
     */
    public function countRecent()
    {
        return $this->countJobsByType(JobReferenceType::Recent, $this->recentJobExpires);
    }

    /**
     * Get the count of failed jobs within the retention window.
     *
     * @return int
     */
    public function countFailed()
    {
        return $this->countJobsByType(JobReferenceType::Failed, $this->failedJobExpires);
    }

    /**
     * Get the count of pending jobs within the retention window.
     *
     * @return int
     */
    public function countPending()
    {
        return $this->countJobsByType(JobReferenceType::Pending, $this->pendingJobExpires);
    }

    /**
     * Get the count of completed jobs within the retention window.
     *
     * @return int
     */
    public function countCompleted()
    {
        return $this->countJobsByType(JobReferenceType::Completed, $this->completedJobExpires);
    }

    /**
     * Get the count of silenced jobs within the retention window.
     *
     * @return int
     */
    public function countSilenced()
    {
        return $this->countJobsByType(JobReferenceType::Silenced, $this->completedJobExpires);
    }

    /**
     * Get the count of the recently failed jobs within the retention window.
     *
     * @return int
     */
    public function countRecentlyFailed()
    {
        return $this->countJobsByType(JobReferenceType::RecentFailed, $this->recentFailedJobExpires);
    }

    /**
     * Get a chunk of jobs from the given type set using a keyset cursor on (score, id).
     *
     * @param  \Laravel\Horizon\Enums\JobReferenceType  $type
     * @param  string|null  $afterIndex
     * @return \Illuminate\Support\Collection
     */
    protected function getJobsByType(JobReferenceType $type, $afterIndex)
    {
        $query = HorizonJobReference::where('type', $type)
            ->orderBy('score', 'desc')
            ->orderBy('id', 'desc');

        if ($this->looksLikeCursor($afterIndex)) {
            [$cursorScore, $cursorId] = $this->decodeCursor((string) $afterIndex);

            $query->where(function ($q) use ($cursorScore, $cursorId) {
                $q->where('score', '<', $cursorScore)
                    ->orWhere(function ($q2) use ($cursorScore, $cursorId) {
                        $q2->where('score', $cursorScore)->where('id', '<', $cursorId);
                    });
            });
        }

        $rows = $query->limit(50)->get(['job_id', 'score', 'id']);

        if ($rows->isEmpty()) {
            return collect();
        }

        $cursors = $rows->mapWithKeys(fn ($row) => [
            (string) $row->job_id => $this->encodeCursor($row->score, $row->id),
        ])->all();

        $jobIds = $rows->pluck('job_id')->all();
        $byId = HorizonJob::whereIn('id', $jobIds)->get()->keyBy('id');

        return collect($jobIds)
            ->map(fn ($id) => $byId->get($id))
            ->filter()
            ->values()
            ->map(function ($job) use ($cursors) {
                $presented = $this->presentJob($job);
                $presented->index = $cursors[(string) $job->id] ?? null;

                return $presented;
            });
    }

    /**
     * Determine whether the given value is a real keyset cursor (vs. a first-page sentinel).
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function looksLikeCursor($value): bool
    {
        return is_string($value) && str_contains($value, ':');
    }

    /**
     * Encode a (score, id) pair into an opaque cursor string.
     *
     * @param  int  $score
     * @param  int  $id
     * @return string
     */
    protected function encodeCursor($score, $id): string
    {
        return $score.':'.$id;
    }

    /**
     * Decode an opaque cursor string into a (score, id) pair.
     *
     * @param  string  $cursor
     * @return array{0:int,1:int}
     */
    protected function decodeCursor(string $cursor): array
    {
        $parts = explode(':', $cursor, 2);

        return [(int) $parts[0], (int) ($parts[1] ?? 0)];
    }

    /**
     * Get the number of jobs in a given type set within the retention window.
     *
     * @param  \Laravel\Horizon\Enums\JobReferenceType  $type
     * @param  int  $minutes
     * @return int
     */
    protected function countJobsByType(JobReferenceType $type, $minutes)
    {
        $cutoff = $this->cutoffScore($minutes);

        return HorizonJobReference::where('type', $type)
            ->where('score', '>=', $cutoff)
            ->count();
    }

    /**
     * Get the score cutoff for the given retention window in minutes.
     *
     * @param  int  $minutes
     * @return int
     */
    protected function cutoffScore($minutes)
    {
        return CarbonImmutable::now()->subMinutes($minutes)->getTimestamp() * 1_000_000;
    }

    /**
     * Convert a microtime float into a microsecond score.
     *
     * @param  float  $time
     * @return int
     */
    protected function microtimeToScore($time)
    {
        [$seconds, $micro] = $this->splitMicrotime($time);

        return $seconds * 1_000_000 + $micro;
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

        $byId = HorizonJob::whereIn('id', $ids)->get()->keyBy('id');

        $ordered = collect($ids)
            ->map(fn ($id) => $byId->get($id))
            ->filter()
            ->values();

        return $this->indexJobs($ordered, $indexFrom);
    }

    /**
     * Map a job model to the public shape expected by Horizon.
     *
     * @param  \Laravel\Horizon\Models\HorizonJob  $model
     * @return object
     */
    protected function presentJob($model)
    {
        return (object) [
            'id' => (string) $model->id,
            'connection' => $model->connection,
            'queue' => $model->queue,
            'name' => $model->name,
            'status' => $model->status?->value,
            'payload' => $model->payload,
            'exception' => $model->exception,
            'context' => $model->context,
            'failed_at' => $this->formatMicrotime($model->failed_at),
            'completed_at' => $this->formatMicrotime($model->completed_at),
            'retried_by' => $model->retried_by === null ? null : json_encode($model->retried_by),
            'reserved_at' => $this->formatMicrotime($model->reserved_at),
            'delay' => $model->delay === null ? null : (string) $model->delay,
        ];
    }

    /**
     * Attach a sequential index column starting at the given offset.
     *
     * @param  \Illuminate\Support\Collection  $jobs
     * @param  int  $indexFrom
     * @return \Illuminate\Support\Collection
     */
    protected function indexJobs($jobs, $indexFrom)
    {
        return $jobs->map(function ($job) use (&$indexFrom) {
            $presented = $this->presentJob($job);
            $presented->index = $indexFrom;

            $indexFrom++;

            return $presented;
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
        $time = microtime(true);
        $now = CarbonImmutable::now();

        HorizonJob::upsert([[
            'id' => $payload->id(),
            'connection' => $connection,
            'queue' => $queue,
            'name' => Arr::get($payload->decoded, 'displayName'),
            'status' => JobStatus::Pending->value,
            'payload' => $payload->value,
            'expires_at' => $now->addMinutes($this->pendingJobExpires),
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['id'], ['connection', 'queue', 'name', 'status', 'payload', 'expires_at', 'updated_at']);

        $this->storeJobReference(JobReferenceType::Recent, $queue, $payload, $time);
        $this->storeJobReference(JobReferenceType::Pending, $queue, $payload, $time);
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
        if (! $job = HorizonJob::find($payload->id())) {
            return;
        }

        $job->status = JobStatus::Reserved;
        $job->payload = $payload->value;
        $job->reserved_at = $this->microtimeToCarbon(microtime(true));
        $job->save();
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
        if (! $job = HorizonJob::find($payload->id())) {
            return;
        }

        $job->status = JobStatus::Pending;
        $job->payload = $payload->value;
        $job->delay = (int) $delay;
        $job->save();
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
        $time = microtime(true);
        $now = CarbonImmutable::now();

        HorizonJob::upsert([[
            'id' => $payload->id(),
            'connection' => $connection,
            'queue' => $queue,
            'name' => Arr::get($payload->decoded, 'displayName'),
            'status' => JobStatus::Completed->value,
            'payload' => $payload->value,
            'completed_at' => $this->microtimeToCarbon($time),
            'expires_at' => $now->addMinutes($this->monitoredJobExpires),
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['id'], ['connection', 'queue', 'name', 'status', 'payload', 'completed_at', 'expires_at', 'updated_at']);

        $this->storeJobReference(JobReferenceType::Monitored, $queue, $payload, $time);
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
        foreach ($payloads as $payload) {
            if (! $job = HorizonJob::find($payload->id())) {
                continue;
            }

            $job->status = JobStatus::Pending;
            $job->payload = $payload->value;
            $job->delay = 0;
            $job->save();
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
        if ($failed) {
            return;
        }

        if ($payload->isRetry()) {
            $this->updateRetryInformationOnParent($payload, $failed);
        }

        if (! $job = HorizonJob::find($payload->id())) {
            return;
        }

        $time = microtime(true);

        $job->status = JobStatus::Completed;
        $job->completed_at = $this->microtimeToCarbon($time);
        $job->expires_at = CarbonImmutable::now()->addMinutes($this->completedJobExpires);
        $job->save();

        $this->removeJobReference(JobReferenceType::Pending, $payload->id());
        $this->storeJobReference(
            $silenced ? JobReferenceType::Silenced : JobReferenceType::Completed,
            $job->queue,
            $payload,
            $time
        );
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
        if (! $parent = HorizonJob::find($payload->retryOf())) {
            return;
        }

        $parent->retried_by = collect($parent->retried_by ?? [])->map(function ($retry) use ($payload, $failed) {
            return $retry['id'] === $payload->id()
                ? Arr::set($retry, 'status', $failed ? JobStatus::Failed->value : JobStatus::Completed->value)
                : $retry;
        })->all();

        $parent->save();
    }

    /**
     * Delete the given monitored jobs by IDs.
     *
     * @param  array  $ids
     * @return void
     */
    public function deleteMonitored(array $ids)
    {
        $expiresAt = CarbonImmutable::now()->addDays(7);

        HorizonJob::whereIn('id', $ids)->update([
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Trim the recent job list.
     *
     * @return void
     */
    public function trimRecentJobs()
    {
        $this->trimReferences(JobReferenceType::Recent, $this->recentJobExpires);
        $this->trimReferences(JobReferenceType::RecentFailed, $this->recentFailedJobExpires);
        $this->trimReferences(JobReferenceType::Pending, $this->pendingJobExpires);
        $this->trimReferences(JobReferenceType::Completed, $this->completedJobExpires);
        $this->trimReferences(JobReferenceType::Silenced, $this->completedJobExpires);
    }

    /**
     * Trim the failed job list.
     *
     * @return void
     */
    public function trimFailedJobs()
    {
        $this->trimReferences(JobReferenceType::Failed, $this->failedJobExpires);
    }

    /**
     * Trim the monitored job list.
     *
     * @return void
     */
    public function trimMonitoredJobs()
    {
        $this->trimReferences(JobReferenceType::Monitored, $this->monitoredJobExpires);
    }

    /**
     * Delete reference rows older than the given retention window.
     *
     * @param  \Laravel\Horizon\Enums\JobReferenceType  $type
     * @param  int  $minutes
     * @return void
     */
    protected function trimReferences(JobReferenceType $type, $minutes)
    {
        $cutoff = $this->cutoffScore($minutes);

        HorizonJobReference::where('type', $type)
            ->where('score', '<', $cutoff)
            ->chunkById(1000, function ($chunk) {
                HorizonJobReference::whereIn('id', $chunk->modelKeys())->delete();
            });
    }

    /**
     * Find a failed job by ID.
     *
     * @param  string  $id
     * @return \stdClass|null
     */
    public function findFailed($id)
    {
        $job = HorizonJob::find($id);

        if (! $job || $job->status !== JobStatus::Failed) {
            return null;
        }

        return $this->presentJob($job);
    }

    /**
     * Mark the job as failed.
     *
     * @param  \Exception  $exception
     * @param  string  $connection
     * @param  string  $queue
     * @param  \Laravel\Horizon\JobPayload  $payload
     * @return void
     */
    public function failed($exception, $connection, $queue, JobPayload $payload)
    {
        $time = microtime(true);
        $now = CarbonImmutable::now();

        HorizonJob::upsert([[
            'id' => $payload->id(),
            'connection' => $connection,
            'queue' => $queue,
            'name' => Arr::get($payload->decoded, 'displayName'),
            'status' => JobStatus::Failed->value,
            'payload' => $payload->value,
            'exception' => (string) $exception,
            'context' => method_exists($exception, 'context')
                ? json_encode($exception->context())
                : null,
            'failed_at' => $this->microtimeToCarbon($time),
            'expires_at' => $now->addMinutes($this->failedJobExpires),
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['id'], ['connection', 'queue', 'name', 'status', 'payload', 'exception', 'context', 'failed_at', 'expires_at', 'updated_at']);

        $this->storeJobReference(JobReferenceType::Failed, $queue, $payload, $time);
        $this->storeJobReference(JobReferenceType::RecentFailed, $queue, $payload, $time);
        $this->removeJobReference(JobReferenceType::Pending, $payload->id());
        $this->removeJobReference(JobReferenceType::Completed, $payload->id());
        $this->removeJobReference(JobReferenceType::Silenced, $payload->id());
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
        if (! $job = HorizonJob::find($id)) {
            return;
        }

        $retries = $job->retried_by ?? [];

        $retries[] = [
            'id' => $retryId,
            'status' => JobStatus::Pending->value,
            'retried_at' => CarbonImmutable::now()->getTimestamp(),
        ];

        $job->retried_by = $retries;
        $job->save();
    }

    /**
     * Delete a failed job by ID.
     *
     * @param  string  $id
     * @return int
     */
    public function deleteFailed($id)
    {
        $job = HorizonJob::find($id);

        if (! $job || $job->status !== JobStatus::Failed) {
            return 0;
        }

        $job->delete();
        HorizonJobReference::where('job_id', $id)->delete();

        return 1;
    }

    /**
     * Delete pending and reserved jobs for a queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function purge($queue)
    {
        $ids = HorizonJob::where('queue', $queue)
            ->whereNotIn('status', [JobStatus::Completed, JobStatus::Failed])
            ->pluck('id')
            ->all();

        if (empty($ids)) {
            return 0;
        }

        foreach (array_chunk($ids, 1000) as $chunk) {
            HorizonJob::whereIn('id', $chunk)->delete();
            HorizonJobReference::whereIn('job_id', $chunk)->delete();
        }

        return count($ids);
    }

    /**
     * Store a reference row linking a job to a type / queue ordered by microtime.
     *
     * @param  \Laravel\Horizon\Enums\JobReferenceType  $type
     * @param  string  $queue
     * @param  \Laravel\Horizon\JobPayload  $payload
     * @param  float  $time
     * @return void
     */
    protected function storeJobReference(JobReferenceType $type, $queue, JobPayload $payload, $time)
    {
        $now = CarbonImmutable::now();

        HorizonJobReference::upsert([[
            'type' => $type->value,
            'job_id' => $payload->id(),
            'queue' => $queue,
            'score' => $this->microtimeToScore($time),
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['type', 'job_id'], ['queue', 'score', 'updated_at']);
    }

    /**
     * Remove a reference row for a job.
     *
     * @param  \Laravel\Horizon\Enums\JobReferenceType  $type
     * @param  string  $jobId
     * @return void
     */
    protected function removeJobReference(JobReferenceType $type, $jobId)
    {
        HorizonJobReference::where('type', $type)
            ->where('job_id', $jobId)
            ->delete();
    }

    /**
     * Convert a microtime float into a locale-safe Carbon instance.
     *
     * @param  float  $time
     * @return \Carbon\CarbonImmutable
     */
    protected function microtimeToCarbon($time)
    {
        [$seconds, $micro] = $this->splitMicrotime($time);

        return CarbonImmutable::createFromTimestamp($seconds)->setMicroseconds($micro);
    }

    /**
     * Format a Carbon instance (or null) as a Unix microtime decimal string.
     *
     * @param  \Carbon\CarbonInterface|null  $value
     * @return string|null
     */
    protected function formatMicrotime($value)
    {
        if (! $value) {
            return null;
        }

        return $value->format('U.u');
    }

    /**
     * Split a microtime float into [seconds, microseconds] without locale bias.
     *
     * @param  float  $time
     * @return array{0:int,1:int}
     */
    protected function splitMicrotime($time)
    {
        $string = str_replace(',', '.', (string) $time);

        $parts = explode('.', $string);

        $seconds = (int) $parts[0];
        $micro = isset($parts[1]) ? (int) str_pad(substr($parts[1], 0, 6), 6, '0') : 0;

        return [$seconds, $micro];
    }
}
