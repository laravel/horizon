<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\DatabaseQueue;
use Laravel\Horizon\Events\JobDeleted;
use Laravel\Horizon\Events\JobPending;
use Laravel\Horizon\Events\JobPushed;
use Laravel\Horizon\Events\JobReleased;
use Laravel\Horizon\Events\JobReserved;
use Laravel\Horizon\Jobs\HorizonDatabaseJob;
use Laravel\Horizon\Tests\DatabaseIntegrationTest;

class DatabaseQueueConnectorTest extends DatabaseIntegrationTest
{
    public function test_database_connection_returns_horizon_database_queue()
    {
        $connection = Queue::connection('database');

        $this->assertInstanceOf(DatabaseQueue::class, $connection);
    }

    public function test_push_emits_pending_and_pushed_events_and_returns_horizon_uuid()
    {
        Event::fake([JobPending::class, JobPushed::class]);

        $id = Queue::push(new Jobs\BasicJob);

        $this->assertNotEmpty($id);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $id);

        Event::assertDispatched(JobPending::class, fn ($event) => $event->payload->id() === $id);
        Event::assertDispatched(JobPushed::class, fn ($event) => $event->payload->id() === $id);

        $this->assertSame(1, DB::table('jobs')->count());

        $payload = json_decode(DB::table('jobs')->first()->payload, true);

        $this->assertSame($id, $payload['id']);
        $this->assertSame($id, $payload['uuid']);
    }

    public function test_later_emits_pending_and_pushed_events_and_persists_delay()
    {
        Event::fake([JobPending::class, JobPushed::class]);

        $id = Queue::later(60, new Jobs\BasicJob);

        $this->assertNotEmpty($id);

        Event::assertDispatched(JobPending::class);
        Event::assertDispatched(JobPushed::class);

        $record = DB::table('jobs')->first();

        $this->assertGreaterThan(time(), $record->available_at);
    }

    public function test_pop_emits_reserved_event_and_returns_horizon_database_job()
    {
        Queue::push(new Jobs\BasicJob);

        Event::fake([JobReserved::class]);

        $job = Queue::connection('database')->pop('default');

        $this->assertInstanceOf(HorizonDatabaseJob::class, $job);

        Event::assertDispatched(JobReserved::class, 1);
    }

    public function test_delete_emits_deleted_event_with_job_and_payload()
    {
        Queue::push(new Jobs\BasicJob);

        $job = Queue::connection('database')->pop('default');

        Event::fake([JobDeleted::class]);

        $job->delete();

        Event::assertDispatched(JobDeleted::class, function ($event) use ($job) {
            return $event->job === $job
                && $event->payload->id() !== null;
        });

        $this->assertSame(0, DB::table('jobs')->count());
    }

    public function test_release_emits_released_event_with_delay()
    {
        Queue::push(new Jobs\BasicJob);

        $job = Queue::connection('database')->pop('default');

        Event::fake([JobReleased::class]);

        $job->release(30);

        Event::assertDispatched(JobReleased::class, fn ($event) => $event->delay === 30);
    }

    public function test_clear_queue_also_purges_horizon_pending_metadata()
    {
        $repository = $this->app->make(\Laravel\Horizon\Contracts\JobRepository::class);

        Queue::push(new Jobs\BasicJob);
        Queue::push(new Jobs\BasicJob);

        $this->assertSame(2, $repository->countPending());
        $this->assertSame(2, DB::table('jobs')->count());

        $deleted = Queue::connection('database')->clear('default');

        $this->assertSame(2, $deleted);
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(0, $repository->countPending());
    }
}
