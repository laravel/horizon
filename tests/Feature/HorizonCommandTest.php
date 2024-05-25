<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Support\Facades\Config;
use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\Tests\Feature\Fakes\MasterSupervisorWithFakeMonitor;
use Laravel\Horizon\Tests\IntegrationTest;

class HorizonCommandTest extends IntegrationTest
{
    public function test_horizon_command_output_when_supervisor_is_present()
    {
        $this->app->bind(MasterSupervisor::class, MasterSupervisorWithFakeMonitor::class);

        $this->with_scenario([
            'local' => [
                'supervisor-1' => [
                    'maxProcesses' => 3,
                ],
            ],
        ]);

        $this->artisan('horizon --environment local')
            ->expectsOutputToContain('Horizon started successfully.')
            ->doesntExpectOutputToContain('No environment configuration found for the environment')
            ->assertExitCode(0);
    }

    public function test_horizon_command_output_when_supervisor_is_not_present()
    {
        $this->app->bind(MasterSupervisor::class, MasterSupervisorWithFakeMonitor::class);

        $this->with_scenario([
            'local' => [
                'supervisor-1' => [
                    'maxProcesses' => 3,
                ],
            ],
        ]);

        $this->artisan('horizon --environment staging')
            ->expectsOutputToContain('Horizon started successfully.')
            ->expectsOutputToContain('No environment configuration found for the environment: staging.')
            ->assertExitCode(0);
    }

    protected function with_scenario(array $supervisorSettings)
    {
        Config::set('horizon.environments', $supervisorSettings);
    }
}
