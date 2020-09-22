<?php

namespace Laravel\Horizon\Tests\Controller;

use Laravel\Horizon\Contracts\JobRepository;
use Mockery;

class FailedJobsControllerTest extends AbstractControllerTest
{
    public function test_failed_job_can_be_deleted()
    {
        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('deleteFailed')->with('1')->andReturn(1);
        $this->app->instance(JobRepository::class, $jobs);

        $response = $this->actingAs(new Fakes\User)
            ->delete('/horizon/api/jobs/failed/1');

        $response->assertNoContent();
    }

    public function test_deleting_unknown_job_returns_not_found_status()
    {
        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('deleteFailed')->with('1')->andReturn(0);
        $this->app->instance(JobRepository::class, $jobs);

        $response = $this->actingAs(new Fakes\User)
            ->delete('/horizon/api/jobs/failed/1');

        $response->assertNotFound();
    }
}
