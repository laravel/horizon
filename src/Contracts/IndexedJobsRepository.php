<?php

declare(strict_types=1);

namespace Laravel\Horizon\Contracts;

interface IndexedJobsRepository
{
    public function getKeysByJobNameAndStatus(string $jobName, string $status): array;

    /**
     * @param int $startingAt
     * @param string $createdAtTo
     * @param string $jobName
     * @param string $createdAtFrom
     * @return \Illuminate\Support\Collection
     */
    public function getIndexedPending($startingAt, $jobName = null, $createdAtFrom = null, $createdAtTo = null);

    public function getIndexedCompleted(string $jobName): array;
}
