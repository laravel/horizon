<?php

namespace Laravel\Horizon\Tests\Controller;

use Laravel\Horizon\Tests\ControllerTest;

class LegacyVueRouterRedirectCustomPathTest extends ControllerTest
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('horizon.path', 'queues');
        $app['config']->set('horizon.proxy_path', 'ops');
    }

    public function test_redirects_honor_custom_horizon_path_and_proxy_path()
    {
        $this->actingAs(new Fakes\User)
            ->get('/queues/metrics')
            ->assertRedirect('/ops/queues/metrics/jobs')
            ->assertStatus(302);

        $this->actingAs(new Fakes\User)
            ->get('/queues/failed/job-42')
            ->assertRedirect('/ops/queues/jobs/failed/job-42');

        // Laravel's URL generator keeps path slashes literal; both encoded and
        // unencoded request paths resolve via where('tag', '.*').
        $this->actingAs(new Fakes\User)
            ->get('/queues/monitoring/'.rawurlencode('tag/with/slashes'))
            ->assertRedirect('/ops/queues/monitoring/tag/with/slashes/jobs');
    }
}
