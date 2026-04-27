<?php

namespace Laravel\Horizon\Tests\Feature;

use Carbon\CarbonImmutable;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Events\MasterSupervisorLooped;
use Laravel\Horizon\Listeners\TrimTags;
use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\Tests\IntegrationTest;
use Mockery;

class TrimTagsTest extends IntegrationTest
{
    public function test_trimmer_has_a_cooldown_period()
    {
        $trim = new TrimTags;

        $repository = Mockery::mock(TagRepository::class);
        $repository->shouldReceive('trimExpired')->twice();
        $this->app->instance(TagRepository::class, $repository);

        // Should not be called first time since date is initialized...
        $trim->handle(new MasterSupervisorLooped(Mockery::mock(MasterSupervisor::class)));

        CarbonImmutable::setTestNow(CarbonImmutable::now()->addMinutes(30));

        // Should only be called twice...
        $trim->handle(new MasterSupervisorLooped(Mockery::mock(MasterSupervisor::class)));
        $trim->handle(new MasterSupervisorLooped(Mockery::mock(MasterSupervisor::class)));
        $trim->handle(new MasterSupervisorLooped(Mockery::mock(MasterSupervisor::class)));

        CarbonImmutable::setTestNow();
    }
}
