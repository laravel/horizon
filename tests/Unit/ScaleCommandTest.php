<?php

namespace Laravel\Horizon\Tests\Unit;

use Laravel\Horizon\Supervisor;
use Laravel\Horizon\SupervisorCommands\Scale;
use Laravel\Horizon\Tests\UnitTest;
use Mockery;

class ScaleCommandTest extends UnitTest
{
    public function test_scale_command_tells_supervisor_to_scale()
    {
        $supervisor = Mockery::mock(Supervisor::class);
        $supervisor->shouldReceive('scale')->once()->with(3);
        $scale = new Scale;
        $scale->process($supervisor, ['scale' => 3]);
    }
}
