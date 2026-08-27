<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\SupervisorFactory;
use Laravel\Horizon\SystemProcessCounter;
use Laravel\Horizon\Tests\Feature\Fakes\SupervisorWithFakeScaling;
use Laravel\Horizon\Tests\Feature\Fixtures\FakeSupervisorFactory;
use Laravel\Horizon\Tests\IntegrationTest;
use Mockery;

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

    public function test_supervisor_command_starts_balanced_number_of_workers()
    {
        $this->withoutRunningWorkers();

        $this->app->instance(SupervisorFactory::class, $factory = new FakeSupervisorFactory(
            SupervisorWithFakeScaling::class
        ));

        $this->artisan('horizon:supervisor', [
            'name' => 'foo',
            'connection' => 'redis',
            '--balance' => 'auto',
            '--min-processes' => 1,
            '--max-processes' => 9,
        ]);

        $this->assertEquals(5, $factory->supervisor->scaledTo);
    }

    public function test_supervisor_command_does_not_start_any_workers_when_min_processes_is_zero()
    {
        $this->withoutRunningWorkers();

        $this->app->instance(SupervisorFactory::class, $factory = new FakeSupervisorFactory(
            SupervisorWithFakeScaling::class
        ));

        $this->artisan('horizon:supervisor', [
            'name' => 'foo',
            'connection' => 'redis',
            '--balance' => 'auto',
            '--min-processes' => 0,
            '--max-processes' => 9,
        ]);

        $this->assertEquals(0, $factory->supervisor->scaledTo);
    }

    private function withoutRunningWorkers()
    {
        $counter = Mockery::mock(SystemProcessCounter::class);
        $counter->shouldReceive('get')->andReturn(0);

        $this->app->instance(SystemProcessCounter::class, $counter);
    }

    private function myNiceness()
    {
        $pid = getmypid();

        return (int) trim(shell_exec("ps -p {$pid} -o nice="));
    }
}
