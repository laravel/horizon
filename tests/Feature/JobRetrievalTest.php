<?php

namespace Laravel\Horizon\Tests\Feature;

use Cake\Chronos\Chronos;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\JobPayload;
use Laravel\Horizon\Tests\IntegrationTest;

class JobRetrievalTest extends IntegrationTest
{
    public function test_pending_jobs_can_be_retrieved()
    {
        $ids = [];

        $ids[] = Queue::push(new Jobs\BasicJob);
        $ids[] = Queue::push(new Jobs\BasicJob);
        $ids[] = Queue::push(new Jobs\BasicJob);
        $ids[] = Queue::push(new Jobs\BasicJob);
        $ids[] = Queue::push(new Jobs\BasicJob);

        $repository = resolve(JobRepository::class);

        $recent = $repository->getRecent();

        // Test getting all jobs...
        $this->assertCount(5, $recent);
        $this->assertEquals($ids[4], $recent->first()->id);
        $this->assertEquals(Jobs\BasicJob::class, $recent->first()->name);
        $this->assertEquals(0, $recent->first()->index);
        $this->assertEquals($ids[0], $recent->last()->id);
        $this->assertEquals(4, $recent->last()->index);

        // Test pagination...
        $recent = $repository->getRecent(1);
        $this->assertCount(3, $recent);
        $this->assertEquals($ids[2], $recent->first()->id);
        $this->assertEquals(2, $recent->first()->index);
        $this->assertEquals($ids[0], $recent->last()->id);
        $this->assertEquals(4, $recent->last()->index);

        // Test no results...
        $recent = $repository->getRecent(4);
        $this->assertCount(0, $recent);
    }

    public function test_recent_jobs_are_correctly_trimmed_and_expired()
    {
        $ids = [];

        $ids[] = Queue::push(new Jobs\BasicJob);
        $ids[] = Queue::push(new Jobs\BasicJob);
        $ids[] = Queue::push(new Jobs\BasicJob);
        $ids[] = Queue::push(new Jobs\BasicJob);
        $ids[] = Queue::push(new Jobs\BasicJob);

        $repository = resolve(JobRepository::class);
        Chronos::setTestNow(Chronos::now()->addHours(3));

        $this->assertEquals(5, Redis::connection('horizon')->zcard('recent_jobs'));

        $repository->trimRecentJobs();
        $this->assertEquals(0, Redis::connection('horizon')->zcard('recent_jobs'));

        // Assert job record has a TTL...
        $repository->completed(new JobPayload(json_encode(['id' => $ids[0]])));
        $this->assertGreaterThan(0, Redis::connection('horizon')->ttl($ids[0]));

        Chronos::setTestNow();
    }

    public function test_paginating_large_job_results_gives_correct_amounts()
    {
        $ids = [];

        for ($i = 0; $i < 75; $i++) {
            $ids[] = Queue::push(new Jobs\BasicJob);
        }

        $repository = resolve(JobRepository::class);

        $pending = $repository->getRecent();
        $this->assertCount(50, $pending);

        $pending = $repository->getRecent($pending->last()->index);
        $this->assertCount(25, $pending);
    }
}
