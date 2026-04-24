<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Queue\Events\JobFailed as LaravelJobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Events\JobFailed as HorizonJobFailed;
use Laravel\Horizon\Tests\DatabaseIntegrationTest;
use RuntimeException;

class DatabaseFailedJobTest extends DatabaseIntegrationTest
{
    public function test_failed_database_job_fires_horizon_job_failed_event_and_is_marked_failed_in_repository()
    {
        Queue::push(new Jobs\FailingJob);

        $this->work();

        $this->assertSame(1, $this->failedJobs());

        $failed = $this->app->make(JobRepository::class)->getFailed()->first();

        $this->assertNotNull($failed);
        $this->assertSame('failed', $failed->status);
        $this->assertNotNull($failed->exception);
        $this->assertNotNull($failed->failed_at);
        $this->assertSame(Jobs\FailingJob::class, $failed->name);
    }

    public function test_marshal_failed_event_dispatches_horizon_event_for_database_jobs()
    {
        Queue::push(new Jobs\FailingJob);

        Event::fake([HorizonJobFailed::class]);

        $this->work();

        Event::assertDispatched(HorizonJobFailed::class, function ($event) {
            return $event->connectionName === 'database'
                && $event->queue === 'default';
        });
    }

    public function test_marshal_failed_event_skips_non_horizon_jobs()
    {
        Event::fake([HorizonJobFailed::class]);

        $listener = new \Laravel\Horizon\Listeners\MarshalFailedEvent(
            $this->app->make(\Illuminate\Contracts\Events\Dispatcher::class)
        );

        $foreignJob = new \stdClass;
        $event = new LaravelJobFailed(
            connectionName: 'sqs',
            job: $this->app->make(\Illuminate\Queue\Jobs\SyncJob::class, [
                'container' => $this->app,
                'payload' => '{}',
                'connectionName' => 'sqs',
                'queue' => 'default',
            ]),
            exception: new RuntimeException('nope')
        );

        $listener->handle($event);

        Event::assertNotDispatched(HorizonJobFailed::class);
    }
}
