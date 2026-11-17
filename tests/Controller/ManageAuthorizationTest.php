<?php

namespace Laravel\Horizon\Tests\Controller;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Events\DashboardActionPerformed;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;
use Laravel\Horizon\Tests\ControllerTest;

class ManageAuthorizationTest extends ControllerTest
{
    public function test_actions_are_allowed_by_default()
    {
        $this->actingAs(new Fakes\User)
            ->postJson('/horizon/api/monitoring', ['tag' => 'first'])
            ->assertSuccessful();

        $this->assertTrue(Horizon::scriptVariables()['can_manage']);
    }

    public function test_read_only_users_cannot_perform_actions()
    {
        Horizon::manage(fn () => false);

        $user = new Fakes\User;

        $this->actingAs($user)->postJson('/horizon/api/monitoring', ['tag' => 'first'])->assertForbidden();
        $this->actingAs($user)->deleteJson('/horizon/api/monitoring/first')->assertForbidden();
        $this->actingAs($user)->postJson('/horizon/api/jobs/retry/some-id')->assertForbidden();
        $this->actingAs($user)->postJson('/horizon/api/batches/retry/some-id')->assertForbidden();

        $this->actingAs($user)->getJson('/horizon/api/stats')->assertOk();
        $this->actingAs($user)->getJson('/horizon/api/monitoring')->assertOk();

        $this->assertFalse(Horizon::scriptVariables()['can_manage']);
    }

    public function test_successful_actions_dispatch_an_event()
    {
        Event::fake([DashboardActionPerformed::class]);

        $user = new Fakes\User;
        $user->id = 7;

        $this->actingAs($user)
            ->postJson('/horizon/api/monitoring', ['tag' => 'first'])
            ->assertSuccessful();

        $this->actingAs($user)
            ->deleteJson('/horizon/api/monitoring/first')
            ->assertSuccessful();

        Event::assertDispatched(DashboardActionPerformed::class, function ($event) use ($user) {
            return $event->action === 'horizon.monitoring.store'
                && $event->user === $user
                && $event->input === ['tag' => 'first'];
        });

        Event::assertDispatched(DashboardActionPerformed::class, function ($event) {
            return $event->action === 'horizon.monitoring-tag.destroy'
                && $event->parameters === ['tag' => 'first'];
        });
    }

    public function test_forbidden_actions_do_not_dispatch_an_event()
    {
        Event::fake([DashboardActionPerformed::class]);

        Horizon::manage(fn () => false);

        $this->actingAs(new Fakes\User)
            ->postJson('/horizon/api/monitoring', ['tag' => 'first'])
            ->assertForbidden();

        Event::assertNotDispatched(DashboardActionPerformed::class);
    }

    public function test_application_provider_uses_the_manage_horizon_gate_when_defined()
    {
        $this->app['env'] = 'production';

        $this->app->register(new class($this->app) extends HorizonApplicationServiceProvider
        {
            protected function gate()
            {
                Gate::define('viewHorizon', fn ($user) => true);
            }
        });

        $this->actingAs($user = new Fakes\User);

        $request = request()->setUserResolver(fn () => $user);

        $this->assertTrue(Horizon::canManage($request));

        Gate::define('manageHorizon', fn ($user) => false);

        $this->assertFalse(Horizon::canManage($request));

        Gate::define('manageHorizon', fn ($user) => true);

        $this->assertTrue(Horizon::canManage($request));
    }
}
