<?php

namespace Laravel\Horizon\Contracts;

interface PendingJobsRepository
{
    /**
     * Delete the jobs with the given IDs
     *
     * @param array $ids
     * @return void
     */
    public function deleteByIds(array $ids): void;
}
