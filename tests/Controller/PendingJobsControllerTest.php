<?php

namespace Laravel\Horizon\Tests\Controller;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Tests\ControllerTest;
use Laravel\Horizon\Tests\Feature\Jobs\BasicJob;

class PendingJobsControllerTest extends ControllerTest
{
    public function test_pending_job_can_be_deleted()
    {
        $id = Queue::push(new BasicJob);

        $this->actingAs(new Fakes\User)
            ->deleteJson('/horizon/api/jobs/pending/'.$id)
            ->assertOk();

        $this->assertSame(0, Queue::size());
        $this->assertSame(0, $this->recentJobs());
        $this->assertSame(0, app(JobRepository::class)->countPending());
        $this->assertNull(app(JobRepository::class)->getJobs([$id])->first());

        $this->work();
        $this->assertSame(0, Queue::size());
    }

    public function test_delayed_job_can_be_deleted()
    {
        $id = Queue::later(60, new BasicJob);

        $this->actingAs(new Fakes\User)
            ->deleteJson('/horizon/api/jobs/pending/'.$id)
            ->assertOk();

        $this->assertSame(0, Queue::size());
        $this->assertSame(0, app(JobRepository::class)->countPending());
    }

    public function test_only_the_given_job_is_deleted()
    {
        $id = Queue::push(new BasicJob);
        $other = Queue::push(new BasicJob);

        $this->actingAs(new Fakes\User)
            ->deleteJson('/horizon/api/jobs/pending/'.$id)
            ->assertOk();

        $this->assertSame(1, Queue::size());
        $this->assertNotNull(app(JobRepository::class)->getJobs([$other])->first());
    }

    public function test_job_that_is_no_longer_on_the_queue_cannot_be_deleted()
    {
        $id = Queue::push(new BasicJob);

        Redis::connection('default')->del($this->queueKey());

        $this->actingAs(new Fakes\User)
            ->deleteJson('/horizon/api/jobs/pending/'.$id)
            ->assertStatus(409);

        $this->assertNotNull(app(JobRepository::class)->getJobs([$id])->first());
    }

    public function test_reserved_job_cannot_be_deleted()
    {
        $id = Queue::push(new BasicJob);

        Queue::pop();

        $this->actingAs(new Fakes\User)
            ->deleteJson('/horizon/api/jobs/pending/'.$id)
            ->assertNotFound();
    }

    public function test_unknown_job_cannot_be_deleted()
    {
        $this->actingAs(new Fakes\User)
            ->deleteJson('/horizon/api/jobs/pending/unknown')
            ->assertNotFound();
    }

    public function test_delayed_job_can_be_run_now()
    {
        $id = Queue::later(60, new BasicJob);

        $this->assertSame(0, Queue::connection()->readyNow());

        $this->actingAs(new Fakes\User)
            ->postJson('/horizon/api/jobs/pending/'.$id.'/run')
            ->assertOk();

        $this->assertSame(1, Queue::connection()->readyNow());
        $this->assertSame(0, Redis::connection('default')->zcard($this->queueKey().':delayed'));

        $this->work();

        $this->assertSame(1, app(JobRepository::class)->countCompleted());
    }

    public function test_job_that_is_not_delayed_cannot_be_run_now()
    {
        $id = Queue::push(new BasicJob);

        $this->actingAs(new Fakes\User)
            ->postJson('/horizon/api/jobs/pending/'.$id.'/run')
            ->assertStatus(409);

        $this->assertSame(1, Queue::connection()->readyNow());
    }

    protected function queueKey()
    {
        return (fn () => method_exists($this, 'getQueueRedisKey')
            ? $this->getQueueRedisKey('default')
            : $this->getQueue('default'))->call(Queue::connection());
    }
}
