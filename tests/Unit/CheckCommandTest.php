<?php

namespace Laravel\Horizon\Tests\Unit;

use Illuminate\Console\OutputStyle;
use Laravel\Horizon\Console\CheckCommand;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\Tests\UnitTest;
use Mockery;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

class CheckCommandTest extends UnitTest
{
    public function test_check_command_returns_exit_code_1_as_error_if_no_master_supervisors_registered()
    {
        $masterSupervisorRepository = Mockery::mock(MasterSupervisorRepository::class);
        $masterSupervisorRepository->shouldReceive('all')->once()->andReturn([]);

        $this->assertSame(1, self::createCheckCommand()->handle($masterSupervisorRepository));
    }

    public function test_check_command_returns_exit_code_1_as_error_if_no_master_supervisor_of_the_current_machine_is_registered()
    {
        $expectedPrefix = MasterSupervisor::basename();
        $wrongPrefix = "x-$expectedPrefix";

        $masterSupervisorRepository = Mockery::mock(MasterSupervisorRepository::class);
        $masterSupervisorRepository->shouldReceive('all')->once()->andReturn([
            (object) ['name' => $wrongPrefix],
        ]);

        $this->assertSame(1, self::createCheckCommand()->handle($masterSupervisorRepository));
    }

    public function test_check_command_returns_exit_code_0_as_success_if_one_master_supervisor_of_the_current_machine_is_registered()
    {
        $expectedPrefix = MasterSupervisor::basename();

        $masterSupervisorRepository = Mockery::mock(MasterSupervisorRepository::class);
        $masterSupervisorRepository->shouldReceive('all')->once()->andReturn([
            (object) ['name' => "$expectedPrefix-0001"],
        ]);

        $this->assertSame(0, self::createCheckCommand()->handle($masterSupervisorRepository));
    }

    public function test_check_command_returns_exit_code_0_as_success_if_multiple_master_supervisors_of_the_current_machine_is_registered()
    {
        $expectedPrefix = MasterSupervisor::basename();

        $masterSupervisorRepository = Mockery::mock(MasterSupervisorRepository::class);
        $masterSupervisorRepository->shouldReceive('all')->once()->andReturn([
            (object) ['name' => "$expectedPrefix-0001"],
            (object) ['name' => "$expectedPrefix-0002"],
        ]);

        $this->assertSame(0, self::createCheckCommand()->handle($masterSupervisorRepository));
    }

    private static function createCheckCommand()
    {
        $checkCommand = new CheckCommand();
        $checkCommand->setOutput(new OutputStyle(new StringInput(''), new NullOutput()));

        return $checkCommand;
    }
}
