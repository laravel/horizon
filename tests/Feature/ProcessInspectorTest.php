<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Exec;
use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\ProcessInspector;
use Laravel\Horizon\Tests\IntegrationTest;
use Mockery;

class ProcessInspectorTest extends IntegrationTest
{
    public function test_finds_orphaned_process_ids()
    {
        $exec = Mockery::mock(Exec::class);
        $exec->shouldReceive('run')->with(Mockery::pattern('/^pgrep -f \'\[h\]orizon\.\*\[ =\]/'))
            ->andReturn([1, 2, 3, 4, 5, 6]);
        $exec->shouldReceive('run')->with('pgrep -f [h]orizon$')->andReturn([6]);
        $exec->shouldReceive('run')->with('pgrep -P 6')->andReturn([2, 3]);
        $exec->shouldReceive('run')->with('pgrep -P 2')->andReturn([4]);
        $exec->shouldReceive('run')->with('pgrep -P 3')->andReturn([5]);
        $this->app->instance(Exec::class, $exec);

        $supervisors = Mockery::mock(SupervisorRepository::class);
        $supervisors->shouldReceive('all')->andReturn([
            [
                'pid' => 2,
                'master' => 'test',
            ],
            [
                'pid' => 3,
                'master' => 'test',
            ],
        ]);
        $this->app->instance(SupervisorRepository::class, $supervisors);

        $masters = Mockery::mock(MasterSupervisorRepository::class);
        $masters->shouldReceive('all')->andReturn([
            [
                'pid' => 6,
                'name' => 'test',
            ],
        ]);
        $this->app->instance(MasterSupervisorRepository::class, $masters);

        $inspector = resolve(ProcessInspector::class);

        $this->assertEquals([1], $inspector->orphaned());
    }

    public function test_it_uses_master_supervisor_basename_to_find_current_processes()
    {
        $exec = Mockery::mock(Exec::class);
        $this->app->instance(Exec::class, $exec);
        $masterBasename = MasterSupervisor::basename();
        $exec->shouldReceive('run')->with(Mockery::pattern("/pgrep -f '\[h\]orizon\.\*\[ =\].*{$masterBasename}-'/"))
            ->andReturn([1]);

        $this->assertEquals([1], resolve(ProcessInspector::class)->current());
    }

    public function test_it_finds_monitored_masters_that_have_supervisor_processes()
    {
        $exec = Mockery::mock(Exec::class);
        $this->app->instance(Exec::class, $exec);
        $exec->shouldReceive('run')->with('pgrep -P 3')->andReturn([1, 2]);
        $exec->shouldReceive('run')->with('pgrep -P 4')->andReturn([]);

        $masters = Mockery::mock(MasterSupervisorRepository::class);
        $this->app->instance(MasterSupervisorRepository::class, $masters);
        $masters->shouldReceive('all')->andReturn([
            [
                'pid' => 3,
            ],
            [
                'pid' => 4,
            ],
        ]);

        $inspector = resolve(ProcessInspector::class);

        $this->assertEquals([
            [
                'pid' => 3,
            ],
        ], $inspector->monitoredMastersWithSupervisors());
    }

    public function test_it_finds_master_processes_without_supervisor_child_processes()
    {
        $exec = Mockery::mock(Exec::class);
        $this->app->instance(Exec::class, $exec);
        $exec->shouldReceive('run')->with('pgrep -f [h]orizon$')->andReturn([1, 2]);
        $exec->shouldReceive('run')->with('pgrep -P 1')->andReturn([[3, 4]]);
        $exec->shouldReceive('run')->with('pgrep -P 2')->andReturn([]);

        $inspector = resolve(ProcessInspector::class);

        $this->assertEquals([2], $inspector->mastersWithoutSupervisors());
    }
}
