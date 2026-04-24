<?php

namespace Laravel\Horizon\Tests\Feature;

use Carbon\CarbonImmutable;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Models\HorizonMasterSupervisor;
use Laravel\Horizon\Models\HorizonSupervisor;
use Laravel\Horizon\Repositories\DatabaseMasterSupervisorRepository;
use Laravel\Horizon\Repositories\DatabaseSupervisorRepository;
use Laravel\Horizon\Tests\DatabaseIntegrationTest;

class DatabaseSupervisorRepositoryTest extends DatabaseIntegrationTest
{
    protected function masterRepo(): DatabaseMasterSupervisorRepository
    {
        return $this->app->make(MasterSupervisorRepository::class);
    }

    protected function supervisorRepo(): DatabaseSupervisorRepository
    {
        return $this->app->make(SupervisorRepository::class);
    }

    public function test_master_supervisor_repository_is_database_implementation()
    {
        $this->assertInstanceOf(DatabaseMasterSupervisorRepository::class, $this->masterRepo());
    }

    public function test_supervisor_repository_is_database_implementation()
    {
        $this->assertInstanceOf(DatabaseSupervisorRepository::class, $this->supervisorRepo());
    }

    public function test_master_names_returns_active_masters_only()
    {
        HorizonMasterSupervisor::create([
            'name' => 'host-A',
            'environment' => 'local',
            'pid' => 100,
            'status' => 'running',
            'supervisors' => ['host-A:supervisor-1'],
            'expires_at' => CarbonImmutable::now()->addSeconds(10),
        ]);

        HorizonMasterSupervisor::create([
            'name' => 'host-B-stale',
            'environment' => 'local',
            'pid' => 101,
            'status' => 'running',
            'supervisors' => [],
            'expires_at' => CarbonImmutable::now()->subSeconds(10),
        ]);

        $this->assertSame(['host-A'], $this->masterRepo()->names());
    }

    public function test_master_find_returns_data_for_active_master()
    {
        HorizonMasterSupervisor::create([
            'name' => 'host-A',
            'environment' => 'local',
            'pid' => 100,
            'status' => 'running',
            'supervisors' => ['host-A:supervisor-1'],
            'expires_at' => CarbonImmutable::now()->addSeconds(10),
        ]);

        $master = $this->masterRepo()->find('host-A');

        $this->assertNotNull($master);
        $this->assertSame('host-A', $master->name);
        $this->assertSame(100, $master->pid);
        $this->assertSame(['host-A:supervisor-1'], $master->supervisors);
    }

    public function test_master_find_returns_null_for_expired_master()
    {
        HorizonMasterSupervisor::create([
            'name' => 'host-A-expired',
            'environment' => 'local',
            'pid' => 100,
            'status' => 'running',
            'supervisors' => [],
            'expires_at' => CarbonImmutable::now()->subSeconds(5),
        ]);

        $this->assertNull($this->masterRepo()->find('host-A-expired'));
    }

    public function test_master_forget_removes_master_and_its_supervisors()
    {
        HorizonMasterSupervisor::create([
            'name' => 'host-A',
            'environment' => 'local',
            'pid' => 100,
            'status' => 'running',
            'supervisors' => ['host-A:supervisor-1'],
            'expires_at' => CarbonImmutable::now()->addSeconds(10),
        ]);

        HorizonSupervisor::create([
            'name' => 'host-A:supervisor-1',
            'master' => 'host-A',
            'pid' => 1000,
            'status' => 'running',
            'processes' => [],
            'options' => [],
            'expires_at' => CarbonImmutable::now()->addSeconds(30),
        ]);

        $this->masterRepo()->forget('host-A');

        $this->assertNull(HorizonMasterSupervisor::find('host-A'));
        $this->assertNull(HorizonSupervisor::find('host-A:supervisor-1'));
    }

    public function test_master_flush_expired_removes_only_expired_rows()
    {
        HorizonMasterSupervisor::create([
            'name' => 'host-A',
            'environment' => 'local',
            'pid' => 100,
            'status' => 'running',
            'supervisors' => [],
            'expires_at' => CarbonImmutable::now()->addSeconds(10),
        ]);

        HorizonMasterSupervisor::create([
            'name' => 'host-B-stale',
            'environment' => 'local',
            'pid' => 101,
            'status' => 'running',
            'supervisors' => [],
            'expires_at' => CarbonImmutable::now()->subSeconds(5),
        ]);

        $this->masterRepo()->flushExpired();

        $this->assertNotNull(HorizonMasterSupervisor::find('host-A'));
        $this->assertNull(HorizonMasterSupervisor::find('host-B-stale'));
    }

    public function test_supervisor_longest_active_timeout_returns_max_timeout_from_options()
    {
        HorizonSupervisor::create([
            'name' => 'host-A:supervisor-1',
            'master' => 'host-A',
            'pid' => 1000,
            'status' => 'running',
            'processes' => [],
            'options' => ['timeout' => 60],
            'expires_at' => CarbonImmutable::now()->addSeconds(30),
        ]);

        HorizonSupervisor::create([
            'name' => 'host-A:supervisor-2',
            'master' => 'host-A',
            'pid' => 1001,
            'status' => 'running',
            'processes' => [],
            'options' => ['timeout' => 120],
            'expires_at' => CarbonImmutable::now()->addSeconds(30),
        ]);

        $this->assertSame(120, $this->supervisorRepo()->longestActiveTimeout());
    }

    public function test_supervisor_longest_active_timeout_returns_zero_for_empty()
    {
        $this->assertSame(0, $this->supervisorRepo()->longestActiveTimeout());
    }

    public function test_supervisor_forget_removes_named_rows()
    {
        HorizonSupervisor::create([
            'name' => 'host-A:supervisor-1',
            'master' => 'host-A',
            'pid' => 1000,
            'status' => 'running',
            'processes' => [],
            'options' => [],
            'expires_at' => CarbonImmutable::now()->addSeconds(30),
        ]);

        $this->supervisorRepo()->forget(['host-A:supervisor-1']);

        $this->assertNull(HorizonSupervisor::find('host-A:supervisor-1'));
    }

    public function test_supervisor_flush_expired_removes_only_expired_rows()
    {
        HorizonSupervisor::create([
            'name' => 'host-A:fresh',
            'master' => 'host-A',
            'pid' => 1000,
            'status' => 'running',
            'processes' => [],
            'options' => [],
            'expires_at' => CarbonImmutable::now()->addSeconds(30),
        ]);

        HorizonSupervisor::create([
            'name' => 'host-A:stale',
            'master' => 'host-A',
            'pid' => 1001,
            'status' => 'running',
            'processes' => [],
            'options' => [],
            'expires_at' => CarbonImmutable::now()->subSeconds(5),
        ]);

        $this->supervisorRepo()->flushExpired();

        $this->assertNotNull(HorizonSupervisor::find('host-A:fresh'));
        $this->assertNull(HorizonSupervisor::find('host-A:stale'));
    }
}
