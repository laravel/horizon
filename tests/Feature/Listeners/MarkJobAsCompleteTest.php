<?php

namespace Laravel\Horizon\Tests\Feature\Listeners;

use Illuminate\Queue\Jobs\RedisJob;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\Silenced;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Events\JobDeleted;
use Laravel\Horizon\JobPayload;
use Laravel\Horizon\Listeners\MarkJobAsComplete;
use Laravel\Horizon\Tests\IntegrationTest;
use Mockery as m;

class MarkJobAsCompleteTest extends IntegrationTest
{
    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('horizon.silenced', [
            'App\\Jobs\\ConfigJob',
        ]);
    }

    public function test_it_can_mark_a_job_as_complete(): void
    {
        $this->runScenario('App\\Jobs\\TestJob', false);
    }

    public function test_it_can_handle_silenced_jobs_from_the_config(): void
    {
        $this->runScenario('App\\Jobs\\ConfigJob', true);
    }

    public function test_it_can_handle_silenced_jobs_from_an_interface(): void
    {
        $this->runScenario(SilencedJob::class, true);
    }

    public function test_it_can_handle_jobs_which_are_not_silenced(): void
    {
        $this->runScenario(NonSilencedJob::class, false);
    }

    public function runScenario(string $job, bool $silenced): void
    {
        $payload = m::mock(JobPayload::class);
        $payload->shouldReceive('commandName')->andReturn($job);
        $payload->shouldReceive('tags')->andReturn([]);
        $payload->shouldReceive('isSilenced')->andReturn(false);

        $job = m::mock(RedisJob::class);
        $job->shouldReceive('hasFailed')->andReturn(false);

        $event = m::mock(JobDeleted::class);
        $event->payload = $payload;
        $event->job = $job;

        $jobs = m::mock(JobRepository::class);
        $jobs->shouldReceive('completed')->once()->with($payload, false, $silenced);

        $tags = m::mock(TagRepository::class);
        $tags->shouldReceive('monitored')->once()->with([])->andReturn([]);

        $listener = new MarkJobAsComplete($jobs, $tags);

        $listener->handle($event);
    }

    public function test_it_can_handle_job_when_silence_override_true(): void
    {
        $this->runScenarioWithMethodOverride(SilencedJobWithMethodTrue::class, true);
    }

    public function test_it_can_handle_job_when_silence_override_false(): void
    {
        $this->runScenarioWithMethodOverride(SilencedJobWithMethodFalse::class, false);
    }
        public function runScenarioWithMethodOverride(string $job, bool $silenced): void
    {
        $payload = m::mock(JobPayload::class);
        $payload->shouldReceive('commandName')->andReturn($job);
        $payload->shouldReceive('tags')->andReturn([]);
        $payload->shouldReceive('isSilenced')->andReturn((new $job())->silenced());

        $job = m::mock(RedisJob::class);
        $job->shouldReceive('hasFailed')->andReturn(false);

        $event = m::mock(JobDeleted::class);
        $event->payload = $payload;
        $event->job = $job;

        $jobs = m::mock(JobRepository::class);
        $jobs->shouldReceive('completed')->once()->with($payload, false, $silenced);

        $tags = m::mock(TagRepository::class);
        $tags->shouldReceive('monitored')->once()->with([])->andReturn([]);

        $listener = new MarkJobAsComplete($jobs, $tags);

        $listener->handle($event);
    }
}

class SilencedJob implements Silenced
{
}

class SilencedJobWithMethodTrue
{
    public function silenced()
    {
        return true;
    }
}

class SilencedJobWithMethodFalse
{
    public function silenced()
    {
        return false;
    }
}

class NonSilencedJob
{
}
