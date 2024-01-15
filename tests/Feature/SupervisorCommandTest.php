<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\SupervisorFactory;
use Laravel\Horizon\Tests\Feature\Fixtures\FakeSupervisorFactory;
use Laravel\Horizon\Tests\IntegrationTest;

class SupervisorCommandTest extends IntegrationTest
{
    public function test_supervisor_command_can_start_supervisor_monitoring()
    {
        $this->app->instance(SupervisorFactory::class, $factory = new FakeSupervisorFactory);
        $this->artisan('horizon:supervisor', ['name' => 'foo', 'connection' => 'redis']);

        $this->assertTrue($factory->supervisor->monitoring);
        $this->assertTrue($factory->supervisor->working);
    }

    public function test_supervisor_command_can_start_paused_supervisors()
    {
        $this->app->instance(SupervisorFactory::class, $factory = new FakeSupervisorFactory);
        $this->artisan('horizon:supervisor', ['name' => 'foo', 'connection' => 'redis', '--paused' => true]);

        $this->assertFalse($factory->supervisor->working);
    }

    public function test_supervisor_command_can_set_process_niceness()
    {
        $this->app->instance(SupervisorFactory::class, $factory = new FakeSupervisorFactory);
        $this->artisan('horizon:supervisor', ['name' => 'foo', 'connection' => 'redis', '--nice' => 10]);

        $this->assertSame(10, $this->myNiceness());
    }

    private function myNiceness()
    {
        $pid = getmypid();

        return (int) trim(`ps -p $pid -o nice=`);
    }
}
