<?php

declare(strict_types=1);

namespace Laravel\Horizon\Repositories;

use Laravel\Horizon\Contracts\IndexedJobsRepository;

class RedisIndexedJobsRepository implements IndexedJobsRepository
{
    public function getKeysByJobNameAndStatus(string $jobName, string $status): array
    {
        return [];
    }
}
