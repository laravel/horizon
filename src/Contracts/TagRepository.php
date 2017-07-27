<?php

namespace Laravel\Horizon\Contracts;

interface TagRepository
{
    /**
     * Get the currently monitored tags.
     *
     * @return array
     */
    public function monitoring();

    /**
     * Return the tags which are being monitored.
     *
     * @param  array  $tags
     * @return array
     */
    public function monitored(array $tags);

    /**
     * Start monitoring the given tag.
     *
     * @param  string  $tag
     * @return void
     */
    public function monitor($tag);

    /**
     * Stop monitoring the given tag.
     *
     * @param  string  $tag
     * @return void
     */
    public function stopMonitoring($tag);

    /**
     * Store the tags for the given job.
     *
     * @param  string  $id
     * @param  array  $tags
     * @return void
     */
    public function add($id, array $tags);

    /**
     * Store the tags for the given job temporarily.
     *
     * @param  int  $minutes
     * @param  string  $id
     * @param  array  $tags
     * @return void
     */
    public function addTemporary($minutes, $id, array $tags);

    /**
     * Get the number of jobs matching a given tag.
     *
     * @param  string  $tag
     * @return int
     */
    public function count($tag);

    /**
     * Get all of the job IDs for a given tag.
     *
     * @param  string  $tag
     * @return array
     */
    public function jobs($tag);

    /**
     * Paginate the job IDs for a given tag.
     *
     * @param  string  $tag
     * @param  int  $startingAt
     * @param  int  $limit
     * @return array
     */
    public function paginate($tag, $startingAt = 0, $limit = 25);

    /**
     * Delete the given tag from storage.
     *
     * @param  string  $tag
     * @return void
     */
    public function forget($tag);
}
