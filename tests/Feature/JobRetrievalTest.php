<?php

namespace Laravel\Horizon\Tests\Feature;

use Cake\Chronos\Chronos;
use Laravel\Horizon\JobPayload;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Tests\IntegrationTest;
use Laravel\Horizon\Contracts\JobRepository;

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

    /**
     * @dataProvider recentJobsPeriodProvider
     */
    public function test_it_correctly_labels_recent_jobs($trim, $period, $failedPeriod)
    {
        config(['horizon.trim.recent' => $trim]);
        config(['horizon.trim.failed' => $trim]);

        $repository = resolve(JobRepository::class);

        $this->assertEquals($period, $repository->recentJobsPeriod());
        $this->assertEquals($failedPeriod, $repository->recentlyFailedJobsPeriod());
    }

    public function recentJobsPeriodProvider()
    {
        return [
            '-100 minutes' => [-100, 'Jobs past hour', 'Failed jobs past hour'],
            '0 minutes' => [0, 'Jobs past hour', 'Failed jobs past hour'],
            '30 minutes' => [30, 'Jobs past hour', 'Failed jobs past hour'],
            '60 minutes' => [60, 'Jobs past hour', 'Failed jobs past hour'],
            '90 minutes' => [90, 'Jobs past 2 hours', 'Failed jobs past 2 hours'],
            '120 minutes' => [120, 'Jobs past 2 hours', 'Failed jobs past 2 hours'],
            '1 day' => [1440, 'Jobs past 1 day', 'Failed jobs past 1 day'],
            '36 hours' => [2160, 'Jobs past 2 days', 'Failed jobs past 2 days'],
            '5 days' => [7200, 'Jobs past 5 days', 'Failed jobs past 5 days'],
        ];
    }
}
