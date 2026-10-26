<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Tests\Feature\Jobs\UniqueJob;
use Laravel\Horizon\Tests\IntegrationTest;

class ClearQueueTest extends IntegrationTest
{
    public function test_clearing_releases_unique_locks_of_ready_jobs()
    {
        UniqueJob::dispatch(1);
        UniqueJob::dispatch(1);
        $this->assertSame(1, Queue::size());

        $this->artisan('horizon:clear', ['--queue' => 'default'])
            ->expectsOutputToContain('Cleared 1 jobs')
            ->assertExitCode(0);

        UniqueJob::dispatch(1);
        $this->assertSame(1, Queue::size());
    }

    public function test_clearing_releases_unique_locks_of_delayed_jobs()
    {
        UniqueJob::dispatch(1)->delay(60);
        $this->assertSame(1, Queue::size());

        $this->assertSame(1, Queue::clear('default'));

        UniqueJob::dispatch(1)->delay(60);
        $this->assertSame(1, Queue::size());
    }

    public function test_clearing_keeps_unique_locks_of_reserved_jobs_that_may_still_be_running()
    {
        UniqueJob::dispatch(1);
        $this->assertNotNull(Queue::pop());

        $this->assertSame(1, Queue::clear('default'));

        UniqueJob::dispatch(1);
        $this->assertSame(0, Queue::size());
    }

    public function test_clearing_releases_unique_locks_of_reserved_jobs_whose_reservation_expired()
    {
        UniqueJob::dispatch(1);
        $this->assertNotNull(Queue::pop());

        $this->travel(5)->minutes();

        $this->assertSame(1, Queue::clear('default'));

        UniqueJob::dispatch(1);
        $this->assertSame(1, Queue::size());
    }

    public function test_clearing_leaves_unrelated_locks_alone()
    {
        UniqueJob::dispatch(1);
        UniqueJob::dispatch(2)->onQueue('other');
        Cache::lock('unrelated')->get();

        $this->assertSame(1, Queue::clear('default'));

        UniqueJob::dispatch(2)->onQueue('other');
        $this->assertSame(1, Queue::size('other'));
        $this->assertFalse(Cache::lock('unrelated')->get());
    }
}
