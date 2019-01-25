<?php

namespace Laravel\Horizon\Tests\Feature;

use Exception;
use Laravel\Horizon\JobPayload;
use Laravel\Horizon\Tests\IntegrationTest;
use Laravel\Horizon\Contracts\JobRepository;

class RedisJobRepositoryTest extends IntegrationTest
{
    public function test_it_can_find_a_failed_job_by_its_id()
    {
        $repository = $this->app->make(JobRepository::class);
        $payload = new JobPayload(json_encode(['id' => 1, 'displayName' => 'foo']));

        $repository->failed(new Exception('Failed Job'), 'redis', 'default', $payload);

        $this->assertEquals(1, $repository->findFailed(1)->id);
    }

    public function test_it_will_not_find_a_failed_job_if_the_job_has_not_failed()
    {
        $repository = $this->app->make(JobRepository::class);
        $payload = new JobPayload(json_encode(['id' => 1, 'displayName' => 'foo']));

        $repository->pushed('redis', 'default', $payload);

        $this->assertNull($repository->findFailed(1));
    }
}
