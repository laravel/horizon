<?php

namespace Laravel\Horizon\Tests\Diagnostics;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Doctor\Facades\Doctor;
use Laravel\Horizon\Diagnostics\HorizonSnapshotIsScheduled;

class HorizonSnapshotIsScheduledTest extends DiagnosticTestCase
{
    public function test_diagnostic_is_registered_with_doctor()
    {
        $this->assertContains(HorizonSnapshotIsScheduled::class, Doctor::registered());
    }

    public function test_diagnostic_passes_when_snapshots_are_scheduled()
    {
        $schedule = new Schedule;
        $schedule->command('horizon:snapshot')->everyFiveMinutes();

        $this->app->instance(Schedule::class, $schedule);

        $result = (new HorizonSnapshotIsScheduled)->check();

        $this->assertSame('pass', $result->status->value);
        $this->assertSame('Horizon metric snapshots are scheduled.', $result->summary);
    }

    public function test_diagnostic_warns_when_snapshots_are_not_scheduled_in_production()
    {
        $this->app->instance(Schedule::class, new Schedule);

        $result = (new HorizonSnapshotIsScheduled)->check();

        $this->assertSame('warn', $result->status->value);
        $this->assertSame('Horizon metric snapshots are not scheduled.', $result->summary);
        $this->assertStringContainsString('horizon:snapshot', $result->remediation);
    }

    public function test_diagnostic_notices_when_snapshots_are_not_scheduled_outside_production()
    {
        config(['doctor.environments' => ['local' => ['testing']]]);

        $this->app->instance(Schedule::class, new Schedule);

        $result = (new HorizonSnapshotIsScheduled)->check();

        $this->assertSame('notice', $result->status->value);
        $this->assertSame('Horizon metric snapshots are not scheduled.', $result->summary);
    }
}
