<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Tests\IntegrationTest;

class FailedJobTest extends IntegrationTest
{
    public function test_failed_jobs_are_placed_in_the_failed_job_table()
    {
        $id = Queue::push(new Jobs\FailingJob);
        $this->work();
        $this->assertSame(1, $this->failedJobs());
        $this->assertGreaterThan(0, Redis::connection('horizon')->ttl($id));

        $job = resolve(JobRepository::class)->getJobs([$id])[0];

        $this->assertTrue(isset($job->exception));
        $this->assertTrue(isset($job->failed_at));
        $this->assertSame('failed', $job->status);
        $this->assertIsNumeric($job->failed_at);
        $this->assertSame(Jobs\FailingJob::class, $job->name);
    }

    public function test_tags_for_failed_jobs_are_stored_in_redis()
    {
        $id = Queue::push(new Jobs\FailingJob);
        $this->work();
        $ids = resolve(TagRepository::class)->jobs('failed:first');
        $this->assertEquals([$id], $ids);
    }

    public function test_failed_job_tags_have_an_expiration()
    {
        Queue::push(new Jobs\FailingJob);
        $this->work();
        $ttl = Redis::connection('horizon')->pttl('failed:first');
        $this->assertNotNull($ttl);
        $this->assertGreaterThan(0, $ttl);
    }
}
