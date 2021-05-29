<?php

declare(strict_types=1);

namespace Laravel\Horizon\Contracts;

interface IndexedJobsRepository
{
    public function getKeysByJobNameAndStatus(string $jobName, string $status): array;
}
