<?php

namespace Laravel\Horizon\Tests\Feature;

use Exception;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\JobPayload;
use Laravel\Horizon\Tests\IntegrationTest;
use Throwable;

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

    public function test_it_saves_microseconds_as_a_float_and_disregards_the_locale()
    {
        $originalLocale = setlocale(LC_NUMERIC, 0);

        setlocale(LC_NUMERIC, 'fr_FR');

        try {
            $repository = $this->app->make(JobRepository::class);
            $payload = new JobPayload(json_encode(['id' => 1, 'displayName' => 'foo']));

            $repository->pushed('redis', 'default', $payload);
            $repository->reserved('redis', 'default', $payload);

            $result = $repository->getRecent()[0];

            $this->assertStringNotContainsString(',', $result->reserved_at);
        } catch (Exception $e) {
            setlocale(LC_NUMERIC, $originalLocale);

            throw $e;
        } catch (Throwable $e) {
            setlocale(LC_NUMERIC, $originalLocale);

            throw $e;
        }
    }
}
