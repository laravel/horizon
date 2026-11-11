<?php

namespace Laravel\Horizon\Tests\Diagnostics;

use Laravel\Doctor\Facades\Doctor;
use Laravel\Horizon\Diagnostics\HorizonIsRunning;
use RuntimeException;

class HorizonIsRunningTest extends DiagnosticTestCase
{
    public function test_diagnostic_is_registered_with_doctor()
    {
        $this->assertContains(HorizonIsRunning::class, Doctor::registered());
    }

    public function test_diagnostic_fails_when_horizon_redis_connection_is_unreachable_in_production()
    {
        $this->fakeMasters(new RuntimeException('Connection refused'));

        $result = (new HorizonIsRunning)->check();

        $this->assertSame('fail', $result->status->value);
        $this->assertSame('The Horizon Redis connection could not be reached.', $result->summary);
        $this->assertSame('Connection refused', $result->details);
        $this->assertStringContainsString('horizon.use', $result->remediation);
    }

    public function test_diagnostic_warns_when_horizon_redis_connection_is_unreachable_outside_production()
    {
        config(['doctor.environments' => ['local' => ['testing']]]);

        $this->fakeMasters(new RuntimeException('Connection refused'));

        $result = (new HorizonIsRunning)->check();

        $this->assertSame('warn', $result->status->value);
        $this->assertSame('The Horizon Redis connection could not be reached.', $result->summary);
    }

    public function test_diagnostic_fails_when_horizon_is_not_running_in_production()
    {
        $this->fakeMasters([]);

        $result = (new HorizonIsRunning)->check();

        $this->assertSame('fail', $result->status->value);
        $this->assertSame('Horizon is not running.', $result->summary);
        $this->assertStringContainsString('php artisan horizon', $result->remediation);
    }

    public function test_diagnostic_notices_when_horizon_is_not_running_outside_production()
    {
        config(['doctor.environments' => ['local' => ['testing']]]);

        $this->fakeMasters([]);

        $result = (new HorizonIsRunning)->check();

        $this->assertSame('notice', $result->status->value);
        $this->assertSame('Horizon is not running.', $result->summary);
    }

    public function test_diagnostic_warns_when_any_master_supervisor_is_paused()
    {
        $this->fakeMasters([
            (object) ['name' => 'host-1', 'status' => 'paused'],
            (object) ['name' => 'host-2', 'status' => 'running'],
        ]);

        $result = (new HorizonIsRunning)->check();

        $this->assertSame('warn', $result->status->value);
        $this->assertSame('At least one Horizon master supervisor is paused.', $result->summary);
        $this->assertStringContainsString('horizon:continue', $result->remediation);
    }

    public function test_diagnostic_passes_when_every_master_supervisor_is_running()
    {
        $this->fakeMasters([
            (object) ['name' => 'host-1', 'status' => 'running'],
            (object) ['name' => 'host-2', 'status' => 'running'],
        ]);

        $result = (new HorizonIsRunning)->check();

        $this->assertSame('pass', $result->status->value);
        $this->assertSame('Horizon is running.', $result->summary);
    }
}
