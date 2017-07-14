<?php

namespace Laravel\Horizon\Tests\Feature;

use Mockery;
use Cake\Chronos\Chronos;
use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\Tests\IntegrationTest;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Listeners\TrimRecentJobs;
use Laravel\Horizon\Events\MasterSupervisorLooped;

class TrimRecentJobsTest extends IntegrationTest
{
    public function test_trimmer_has_a_cooldown_period()
    {
        $trim = new TrimRecentJobs;

        $repository = Mockery::mock(JobRepository::class);
        $repository->shouldReceive('trimRecentJobs')->twice();
        $this->app->instance(JobRepository::class, $repository);

        // Should not be called first time since date is initialized...
        $trim->handle(new MasterSupervisorLooped(Mockery::mock(MasterSupervisor::class)));

        Chronos::setTestNow(Chronos::now()->addMinutes(30));

        // Should only be called twice...
        $trim->handle(new MasterSupervisorLooped(Mockery::mock(MasterSupervisor::class)));
        $trim->handle(new MasterSupervisorLooped(Mockery::mock(MasterSupervisor::class)));
        $trim->handle(new MasterSupervisorLooped(Mockery::mock(MasterSupervisor::class)));

        Chronos::setTestNow();
    }
}
