<?php

namespace Laravel\Horizon\Tests\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Events\JobPending;
use Laravel\Horizon\Events\JobPushed;
use Laravel\Horizon\Events\JobReserved;
use Laravel\Horizon\Tests\Feature\Jobs\BasicJob;

class DatabaseQueueProcessingTest extends DatabaseIntegrationTestCase
{
    public function test_pending_jobs_are_recorded_in_horizon_jobs_when_pushed()
    {
        Queue::push(new BasicJob);

        $this->assertSame(1, $this->recentJobs());
        $this->assertSame('pending', DB::table('horizon_jobs')->value('status'));
    }

    public function test_pending_delayed_jobs_are_recorded_in_horizon_jobs()
    {
        Queue::later(60, new BasicJob);

        $this->assertSame(1, $this->recentJobs());
        $this->assertSame('pending', DB::table('horizon_jobs')->value('status'));
    }

    public function test_pending_event_is_dispatched_for_database_queue()
    {
        $events = [];
        Event::listen(JobPending::class, function ($event) use (&$events) {
            $events[] = $event;
        });

        Queue::push(new BasicJob);

        $this->assertCount(1, $events);
    }

    public function test_pushed_event_is_dispatched_for_database_queue()
    {
        $events = [];
        Event::listen(JobPushed::class, function ($event) use (&$events) {
            $events[] = $event;
        });

        Queue::push(new BasicJob);

        $this->assertCount(1, $events);
    }

    public function test_pending_job_is_marked_as_reserved_during_processing()
    {
        Queue::push(new BasicJob);

        $status = null;
        Event::listen(JobReserved::class, function ($event) use (&$status) {
            $status = DB::table('horizon_jobs')->value('status');
        });

        $this->work();

        $this->assertSame('reserved', $status);
    }

    public function test_pending_jobs_are_marked_completed_after_being_worked()
    {
        Queue::push(new BasicJob);
        $this->work();

        $recent = resolve(JobRepository::class)->getRecent();
        $this->assertSame('completed', $recent[0]->status);
    }

    public function test_completed_jobs_are_not_normally_stored_as_monitored()
    {
        Queue::push(new BasicJob);
        $this->work();

        $this->assertSame(0, $this->monitoredJobs('first'));
        $this->assertSame(0, $this->monitoredJobs('second'));
    }

    public function test_pending_jobs_are_stored_with_their_tags()
    {
        Queue::push(new BasicJob);

        $payload = json_decode(DB::table('horizon_jobs')->value('payload'), true);

        $this->assertEquals(['first', 'second'], $payload['tags']);
    }
}
