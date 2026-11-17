<?php

namespace Laravel\Horizon\Tests\Diagnostics;

use Laravel\Doctor\Facades\Doctor;
use Laravel\Horizon\Diagnostics\HorizonEnvironmentIsDefined;

class HorizonEnvironmentIsDefinedTest extends DiagnosticTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.env' => 'testing']);

        $this->fakeMasters([]);
    }

    public function test_diagnostic_is_registered_with_doctor()
    {
        $this->assertContains(HorizonEnvironmentIsDefined::class, Doctor::registered());
    }

    public function test_diagnostic_passes_when_the_environment_is_defined()
    {
        config(['horizon.env' => 'production']);

        $result = (new HorizonEnvironmentIsDefined)->check();

        $this->assertSame('pass', $result->status->value);
        $this->assertSame('Horizon defines supervisors for the [production] environment.', $result->summary);
    }

    public function test_diagnostic_passes_when_the_environment_matches_a_wildcard()
    {
        config(['horizon.environments' => ['*' => ['supervisor-1' => ['maxProcesses' => 1]]]]);

        $result = (new HorizonEnvironmentIsDefined)->check();

        $this->assertSame('pass', $result->status->value);
        $this->assertSame('Horizon defines supervisors for the [testing] environment.', $result->summary);
    }

    public function test_diagnostic_uses_the_environment_recorded_by_a_running_master()
    {
        config(['horizon.environments' => [
            'workers' => ['supervisor-1' => ['maxProcesses' => 1]],
        ]]);

        $this->fakeMasters([
            (object) ['environment' => 'workers'],
        ]);

        $result = (new HorizonEnvironmentIsDefined)->check();

        $this->assertSame('pass', $result->status->value);
        $this->assertSame('Horizon defines supervisors for the [workers] environment.', $result->summary);
    }

    public function test_diagnostic_fails_when_the_environment_is_not_defined_in_production()
    {
        $result = (new HorizonEnvironmentIsDefined)->check();

        $this->assertSame('fail', $result->status->value);
        $this->assertSame('Horizon does not define supervisors for the [testing] environment.', $result->summary);
        $this->assertStringContainsString('horizon.environments', $result->remediation);
    }

    public function test_diagnostic_warns_when_the_environment_is_not_defined_outside_production()
    {
        config(['doctor.environments' => ['local' => ['testing']]]);

        $result = (new HorizonEnvironmentIsDefined)->check();

        $this->assertSame('warn', $result->status->value);
        $this->assertSame('Horizon does not define supervisors for the [testing] environment.', $result->summary);
    }

    public function test_diagnostic_fails_when_no_environments_are_defined()
    {
        config(['horizon.environments' => []]);

        $result = (new HorizonEnvironmentIsDefined)->check();

        $this->assertSame('fail', $result->status->value);
        $this->assertSame('No environments are defined in Horizon\'s configuration.', $result->summary);
    }

    public function test_diagnostic_fails_when_the_configuration_is_invalid()
    {
        config(['horizon.environments' => [
            'testing' => ['supervisor-1' => ['minProcesses' => 0]],
        ]]);

        $result = (new HorizonEnvironmentIsDefined)->check();

        $this->assertSame('fail', $result->status->value);
        $this->assertSame('The Horizon environment configuration is invalid.', $result->summary);
        $this->assertStringContainsString('minProcesses', $result->details);
    }

    public function test_diagnostic_fails_in_production_when_no_supervisor_can_start_processes()
    {
        config(['horizon.environments' => [
            'testing' => ['supervisor-1' => ['maxProcesses' => 0]],
        ]]);

        $result = (new HorizonEnvironmentIsDefined)->check();

        $this->assertSame('fail', $result->status->value);
        $this->assertSame('No Horizon supervisors for the [testing] environment can start worker processes.', $result->summary);
        $this->assertStringContainsString('maxProcesses', $result->remediation);
    }

    public function test_diagnostic_warns_outside_production_when_no_supervisor_can_start_processes()
    {
        config([
            'doctor.environments' => ['local' => ['testing']],
            'horizon.environments' => [
                'testing' => ['supervisor-1' => ['maxProcesses' => 0]],
            ],
        ]);

        $result = (new HorizonEnvironmentIsDefined)->check();

        $this->assertSame('warn', $result->status->value);
    }
}
