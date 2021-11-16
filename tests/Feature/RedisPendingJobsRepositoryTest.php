<?php

declare(strict_types=1);

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\PendingJobsRepository;
use Laravel\Horizon\JobPayload;
use Laravel\Horizon\Tests\IntegrationTest;

class RedisPendingJobsRepositoryTest extends IntegrationTest
{
    public function test_it_will_delete_pending_jobs()
    {
        $repositoryPending = $this->app->make(PendingJobsRepository::class);

        $repository = $this->app->make(JobRepository::class);

        $repository->pushed('horizon', 'default', new JobPayload(json_encode(['id' => 1, 'displayName' => 'first'])));
        $repository->pushed('horizon', 'default', new JobPayload(json_encode(['id' => 2, 'displayName' => 'second'])));
        $repository->pushed('horizon', 'default', new JobPayload(json_encode(['id' => 3, 'displayName' => 'third'])));
        $repository->pushed('horizon', 'default', new JobPayload(json_encode(['id' => 4, 'displayName' => 'fourth'])));
        $repository->pushed('horizon', 'default', new JobPayload(json_encode(['id' => 5, 'displayName' => 'fifth'])));

        $repositoryPending->deleteByIds([2,3]);

        $this->assertEquals(3, $repository->countPending());
    }
}
