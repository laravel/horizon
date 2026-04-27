<?php

namespace Laravel\Horizon\Tests\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Tests\Feature\Jobs\FailingJob;

class DatabaseFailedJobTest extends DatabaseIntegrationTestCase
{
    public function test_failed_jobs_are_recorded_in_horizon_jobs_with_failed_status()
    {
        Queue::push(new FailingJob);
        $this->work();

        $this->assertSame(1, $this->failedJobs());

        $job = (array) DB::table('horizon_jobs')->where('status', 'failed')->first();

        $this->assertNotNull($job);
        $this->assertNotNull($job['exception']);
        $this->assertIsNumeric($job['failed_at']);
        $this->assertSame(FailingJob::class, $job['name']);
    }

    public function test_failed_jobs_are_retrievable_through_the_repository()
    {
        Queue::push(new FailingJob);
        $this->work();

        $failed = resolve(JobRepository::class)->getFailed();

        $this->assertCount(1, $failed);
        $this->assertSame('failed', $failed[0]->status);
        $this->assertSame(FailingJob::class, $failed[0]->name);
    }

    public function test_tags_for_failed_jobs_are_stored_in_horizon_tags()
    {
        Queue::push(new FailingJob);
        $this->work();

        $jobId = DB::table('horizon_jobs')->where('status', 'failed')->value('id');

        $this->assertEquals([$jobId], resolve(TagRepository::class)->jobs('failed:first'));
    }

    public function test_failed_job_tags_have_an_expiration_set()
    {
        Queue::push(new FailingJob);
        $this->work();

        $expiresAt = DB::table('horizon_tags')->where('tag', 'failed:first')->value('expires_at');

        $this->assertNotNull($expiresAt);
        $this->assertGreaterThan(time(), $expiresAt);
    }
}
