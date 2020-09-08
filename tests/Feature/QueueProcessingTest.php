<?php

namespace Laravel\Horizon\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Events\JobReserved;
use Laravel\Horizon\Events\JobsMigrated;
use Laravel\Horizon\Tests\IntegrationTest;

class QueueProcessingTest extends IntegrationTest
{
    public function test_legacy_jobs_can_be_processed_without_errors()
    {
        Queue::push('Laravel\Horizon\Tests\Feature\Jobs\LegacyJob');
        $this->work();
    }

    public function test_completed_jobs_are_not_normally_stored_in_completed_database()
    {
        Queue::push(new Jobs\BasicJob);
        $this->work();
        $this->assertSame(0, $this->monitoredJobs('first'));
        $this->assertSame(0, $this->monitoredJobs('second'));
    }

    public function test_pending_jobs_are_stored_in_pending_job_database()
    {
        $id = Queue::push(new Jobs\BasicJob);
        $this->assertSame(1, $this->recentJobs());
        $this->assertSame('pending', Redis::connection('horizon')->hget($id, 'status'));
    }

    public function test_pending_delayed_jobs_are_stored_in_pending_job_database()
    {
        $id = Queue::later(1, new Jobs\BasicJob);
        $this->assertSame(1, $this->recentJobs());
        $this->assertSame('pending', Redis::connection('horizon')->hget($id, 'status'));
    }

    public function test_pending_jobs_are_stored_with_their_tags()
    {
        $id = Queue::push(new Jobs\BasicJob);
        $payload = json_decode(Redis::connection('horizon')->hget($id, 'payload'), true);
        $this->assertEquals(['first', 'second'], $payload['tags']);
    }

    public function test_pending_jobs_are_stored_with_their_type()
    {
        $id = Queue::push(new Jobs\BasicJob);
        $payload = json_decode(Redis::connection('horizon')->hget($id, 'payload'), true);
        $this->assertSame('job', $payload['type']);
    }

    public function test_pending_jobs_are_no_longer_in_pending_database_after_being_worked()
    {
        Queue::push(new Jobs\BasicJob);
        $this->work();

        $recent = resolve(JobRepository::class)->getRecent();
        $this->assertSame('completed', $recent[0]->status);
    }

    public function test_pending_job_is_marked_as_reserved_during_processing()
    {
        $id = Queue::push(new Jobs\BasicJob);

        $status = null;
        Event::listen(JobReserved::class, function ($event) use ($id, &$status) {
            $status = Redis::connection('horizon')->hget($id, 'status');
        });

        $this->work();

        $this->assertSame('reserved', $status);
    }

    public function test_stale_reserved_jobs_are_marked_as_pending_after_migrating()
    {
        $id = Queue::later(CarbonImmutable::now()->addSeconds(0), new Jobs\BasicJob);

        Redis::connection('horizon')->hset($id, 'status', 'reserved');

        $status = null;
        Event::listen(JobsMigrated::class, function ($event) use ($id, &$status) {
            $status = Redis::connection('horizon')->hget($id, 'status');
        });

        $this->work();

        $this->assertSame('pending', $status);
    }
}
