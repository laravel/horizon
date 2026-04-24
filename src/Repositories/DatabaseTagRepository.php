<?php

namespace Laravel\Horizon\Repositories;

use Carbon\CarbonImmutable;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Models\HorizonMonitoredTag;
use Laravel\Horizon\Models\HorizonTag;

class DatabaseTagRepository implements TagRepository
{
    /**
     * Get the currently monitored tags.
     *
     * @return array
     */
    public function monitoring()
    {
        return HorizonMonitoredTag::orderBy('tag')->pluck('tag')->all();
    }

    /**
     * Return the tags which are being monitored.
     *
     * @param  array  $tags
     * @return array
     */
    public function monitored(array $tags)
    {
        return array_intersect($tags, $this->monitoring());
    }

    /**
     * Start monitoring the given tag.
     *
     * @param  string  $tag
     * @return void
     */
    public function monitor($tag)
    {
        HorizonMonitoredTag::updateOrCreate(['tag' => $tag]);
    }

    /**
     * Stop monitoring the given tag.
     *
     * @param  string  $tag
     * @return void
     */
    public function stopMonitoring($tag)
    {
        HorizonMonitoredTag::where('tag', $tag)->delete();
    }

    /**
     * Store the tags for the given job.
     *
     * @param  string  $id
     * @param  array  $tags
     * @return void
     */
    public function add($id, array $tags)
    {
        $this->batchUpsert($id, $tags, null);
    }

    /**
     * Store the tags for the given job temporarily.
     *
     * @param  int  $minutes
     * @param  string  $id
     * @param  array  $tags
     * @return void
     */
    public function addTemporary($minutes, $id, array $tags)
    {
        $this->batchUpsert($id, $tags, CarbonImmutable::now()->addMinutes($minutes));
    }

    /**
     * Upsert the given tag/job pairs in a single statement.
     *
     * @param  string  $id
     * @param  array  $tags
     * @param  \Carbon\CarbonImmutable|null  $expiresAt
     * @return void
     */
    protected function batchUpsert($id, array $tags, ?CarbonImmutable $expiresAt): void
    {
        if (empty($tags)) {
            return;
        }

        $score = $this->currentScore();
        $now = CarbonImmutable::now();

        $rows = array_map(fn ($tag) => [
            'tag' => $tag,
            'job_id' => $id,
            'score' => $score,
            'expires_at' => $expiresAt,
            'created_at' => $now,
            'updated_at' => $now,
        ], array_values(array_unique($tags)));

        HorizonTag::upsert($rows, ['tag', 'job_id'], ['score', 'expires_at', 'updated_at']);
    }

    /**
     * Get the current microtime expressed as a microsecond score.
     *
     * @return int
     */
    protected function currentScore()
    {
        return (int) round(microtime(true) * 1_000_000);
    }

    /**
     * Get the number of jobs matching a given tag.
     *
     * @param  string  $tag
     * @return int
     */
    public function count($tag)
    {
        return HorizonTag::where('tag', $tag)->count();
    }

    /**
     * Get all of the job IDs for a given tag.
     *
     * @param  string  $tag
     * @return array
     */
    public function jobs($tag)
    {
        return HorizonTag::where('tag', $tag)
            ->orderBy('score')
            ->pluck('job_id')
            ->all();
    }

    /**
     * Paginate the job IDs for a given tag.
     *
     * @param  string  $tag
     * @param  int  $startingAt
     * @param  int  $limit
     * @return array
     */
    public function paginate($tag, $startingAt = 0, $limit = 25)
    {
        $ids = HorizonTag::where('tag', $tag)
            ->orderBy('score', 'desc')
            ->offset($startingAt)
            ->limit($limit)
            ->pluck('job_id')
            ->all();

        return collect($ids)
            ->mapWithKeys(fn ($id, $index) => [$index + $startingAt => $id])
            ->all();
    }

    /**
     * Remove the given job IDs from the given tag.
     *
     * @param  array|string  $tags
     * @param  array|string  $ids
     * @return void
     */
    public function forgetJobs($tags, $ids)
    {
        HorizonTag::whereIn('tag', (array) $tags)
            ->whereIn('job_id', (array) $ids)
            ->delete();
    }

    /**
     * Delete the given tag from storage.
     *
     * @param  string  $tag
     * @return void
     */
    public function forget($tag)
    {
        HorizonTag::where('tag', $tag)->delete();
    }
}
