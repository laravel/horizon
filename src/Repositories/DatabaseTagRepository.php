<?php

namespace Laravel\Horizon\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionResolverInterface;
use Laravel\Horizon\Contracts\TagRepository;

class DatabaseTagRepository implements TagRepository
{
    /**
     * The database connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    public $resolver;

    /**
     * Create a new repository instance.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @return void
     */
    public function __construct(ConnectionResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Get the currently monitored tags.
     *
     * @return array
     */
    public function monitoring()
    {
        return $this->monitoredTable()->pluck('tag')->all();
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
        $this->monitoredTable()->updateOrInsert(['tag' => $tag], ['tag' => $tag]);
    }

    /**
     * Stop monitoring the given tag.
     *
     * @param  string  $tag
     * @return void
     */
    public function stopMonitoring($tag)
    {
        $this->monitoredTable()->where('tag', $tag)->delete();
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
        $this->insertTagRows($id, $tags, null);
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
        $this->insertTagRows(
            $id, $tags, CarbonImmutable::now()->addMinutes($minutes)->getTimestamp()
        );
    }

    /**
     * Insert the given tag rows for the given job.
     *
     * @param  string  $id
     * @param  array  $tags
     * @param  int|null  $expiresAt
     * @return void
     */
    protected function insertTagRows($id, array $tags, $expiresAt)
    {
        if (empty($tags)) {
            return;
        }

        $time = str_replace(',', '.', microtime(true));

        foreach ($tags as $tag) {
            $this->table()->updateOrInsert(
                ['tag' => $tag, 'job_id' => (string) $id],
                ['created_at' => $time, 'expires_at' => $expiresAt]
            );
        }
    }

    /**
     * Get the number of jobs matching a given tag.
     *
     * @param  string  $tag
     * @return int
     */
    public function count($tag)
    {
        return $this->activeTagQuery($tag)->count();
    }

    /**
     * Get all of the job IDs for a given tag.
     *
     * @param  string  $tag
     * @return array
     */
    public function jobs($tag)
    {
        return $this->activeTagQuery($tag)
            ->orderBy('created_at')
            ->orderBy('id')
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
        $ids = $this->activeTagQuery($tag)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->offset($startingAt)
            ->limit($limit)
            ->pluck('job_id')
            ->all();

        return collect($ids)->mapWithKeys(function ($id, $index) use ($startingAt) {
            return [$index + $startingAt => $id];
        })->all();
    }

    /**
     * Get a query builder filtered to non-expired rows for the given tag.
     *
     * @param  string  $tag
     * @return \Illuminate\Database\Query\Builder
     */
    protected function activeTagQuery($tag)
    {
        $now = CarbonImmutable::now()->getTimestamp();

        return $this->table()
            ->where('tag', $tag)
            ->where(function ($query) use ($now) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
            });
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
        $this->table()
            ->whereIn('tag', (array) $tags)
            ->whereIn('job_id', array_map('strval', (array) $ids))
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
        $this->table()->where('tag', $tag)->delete();
    }

    /**
     * Get a query builder for the horizon tags table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        return $this->connection()->table('horizon_tags');
    }

    /**
     * Get a query builder for the horizon monitored tags table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function monitoredTable()
    {
        return $this->connection()->table('horizon_monitored_tags');
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
