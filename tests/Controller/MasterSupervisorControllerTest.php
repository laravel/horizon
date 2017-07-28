<?php

namespace Laravel\Horizon\Tests\Controller;

use Laravel\Horizon\Supervisor;
use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\SupervisorOptions;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;

class MasterSupervisorControllerTest extends AbstractControllerTest
{
    public function test_master_supervisor_listing_without_supervisors()
    {
        $master = new MasterSupervisor;
        $master->name = 'risa';
        resolve(MasterSupervisorRepository::class)->update($master);

        $master2 = new MasterSupervisor;
        $master2->name = 'risa-2';
        resolve(MasterSupervisorRepository::class)->update($master2);

        $response = $this->actingAs(new Fakes\User)->get('/horizon/api/masters');

        $response->assertJson([
            'risa' => ['name' => 'risa', 'status' => 'running'],
            'risa-2' => ['name' => 'risa-2', 'status' => 'running'],
        ]);
    }

    public function test_master_supervisor_listing_with_supervisors()
    {
        $master = new MasterSupervisor;
        $master->name = 'risa';
        resolve(MasterSupervisorRepository::class)->update($master);

        $master2 = new MasterSupervisor;
        $master2->name = 'risa-2';
        resolve(MasterSupervisorRepository::class)->update($master2);

        $supervisor = new Supervisor(new SupervisorOptions('risa:name', 'redis'));
        resolve(SupervisorRepository::class)->update($supervisor);

        $response = $this->actingAs(new Fakes\User)->get('/horizon/api/masters');

        $response->assertJson([
            'risa' => [
                'name' => 'risa',
                'status' => 'running',
                'supervisors' => [
                    [
                        'name' => 'risa:name',
                        'master' => 'risa',
                        'status' => 'running',
                        'processes' => ['redis:default' => 0],
                    ],
                ],
            ],
            'risa-2' => [
                'supervisors' => [],
            ],
        ]);
    }
}
