<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\Contracts\HorizonCommandQueue;
use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\MasterSupervisorCommands\AddSupervisor;
use Laravel\Horizon\PhpBinary;
use Laravel\Horizon\SupervisorOptions;
use Laravel\Horizon\Tests\IntegrationTest;

class AddSupervisorTest extends IntegrationTest
{
    public function test_add_supervisor_command_creates_new_supervisor_on_master_process()
    {
        $master = new MasterSupervisor;
        $phpBinary = PhpBinary::path();

        $master->loop();

        new AddSupervisor;
        resolve(HorizonCommandQueue::class)->push($master->commandQueue(), AddSupervisor::class, (new SupervisorOptions('my-supervisor', 'redis'))->toArray());

        $this->assertCount(0, $master->supervisors);

        $master->loop();

        $this->assertCount(1, $master->supervisors);

        $this->assertSame(
            'exec '.$phpBinary.' artisan horizon:supervisor my-supervisor redis --workers-name=default --balance=off --max-processes=1 --min-processes=1 --nice=0 --balance-cooldown=3 --balance-max-shift=1 --backoff=0 --max-time=0 --max-jobs=0 --memory=128 --queue="default" --sleep=3 --timeout=60 --tries=0',
            $master->supervisors->first()->process->getCommandLine()
        );
    }
}
