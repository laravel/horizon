<?php

namespace Laravel\Horizon\Tests\Database;

use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\Tests\Database\Fakes\SupervisorWithFakePool as Supervisor;
use Laravel\Horizon\SupervisorOptions;

class DatabaseMasterSupervisorRepositoryTest extends DatabaseTestCase
{
    public function test_master_supervisor_information_can_be_persisted_and_retrieved()
    {
        $master = new MasterSupervisor;
        $master->supervisors->push(new Supervisor(new SupervisorOptions($master->name.':first', 'redis', 'default')));

        resolve(MasterSupervisorRepository::class)->update($master);

        $record = resolve(MasterSupervisorRepository::class)->find($master->name);

        $this->assertSame($master->name, $record->name);
        $this->assertSame('running', $record->status);
        $this->assertSame([$master->name.':first'], $record->supervisors);
        $this->assertNotNull($record->pid);
    }

    public function test_paused_master_supervisor_status_is_persisted()
    {
        $master = new MasterSupervisor;
        $master->working = false;

        resolve(MasterSupervisorRepository::class)->update($master);

        $this->assertSame('paused', resolve(MasterSupervisorRepository::class)->find($master->name)->status);
    }

    public function test_find_returns_null_when_no_master_supervisor_exists()
    {
        $this->assertNull(resolve(MasterSupervisorRepository::class)->find('nothing'));
    }

    public function test_master_supervisor_can_be_forgotten_and_removes_owned_supervisors()
    {
        $master = new MasterSupervisor;
        $master->supervisors->push(new Supervisor(new SupervisorOptions($master->name.':first', 'redis', 'default')));

        resolve(SupervisorRepository::class)->update($master->supervisors->first());
        resolve(MasterSupervisorRepository::class)->update($master);

        resolve(MasterSupervisorRepository::class)->forget($master->name);

        $this->assertNull(resolve(MasterSupervisorRepository::class)->find($master->name));
        $this->assertNull(resolve(SupervisorRepository::class)->find($master->name.':first'));
    }

    public function test_expired_master_supervisors_can_be_flushed()
    {
        $master = new MasterSupervisor;

        resolve(MasterSupervisorRepository::class)->update($master);

        $this->app['db']->connection()->table('horizon_master_supervisors')
            ->where('name', $master->name)
            ->update(['expires_at' => now()->subSeconds(60)->getTimestamp()]);

        resolve(MasterSupervisorRepository::class)->flushExpired();

        $this->assertNull(resolve(MasterSupervisorRepository::class)->find($master->name));
    }
}
