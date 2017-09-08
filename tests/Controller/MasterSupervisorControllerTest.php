<?php

namespace Laravel\Horizon\Tests\Controller;

use Laravel\Horizon\Supervisor;
use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\SupervisorOptions;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;

class MasterSupervisorControllerTest extends AbstractControllerTest
{
    public function test_master_supervisor_is_signaled_to_pause()
    {
        //master is currently working
        $master = new MasterSupervisor;
        $master->continue();

        $repo = resolve(MasterSupervisorRepository::class);

        $this->actingAs(new Fakes\User);

        //tell the master to pause
        $response = $this->json('POST', '/horizon/api/pause');

        $this->assertEquals(204, $response->getStatusCode());

        //repository is paused
        $this->assertTrue($repo->paused());

        $master->loop();

        //master is paused
        $this->assertFalse($master->working);

        //repository has forgotten the paused state.
        $this->assertFalse($repo->paused());
    }

    public function test_master_supervisor_is_signaled_to_resume()
    {
        //master is currently paused
        $master = new MasterSupervisor;
        $master->pause();

        $repo = resolve(MasterSupervisorRepository::class);

        $this->actingAs(new Fakes\User);

        //command the master to unpause
        $response = $this->json('DELETE', '/horizon/api/pause');

        $this->assertEquals(204, $response->getStatusCode());

        //repository is resumed
        $this->assertTrue($repo->resumed());

        $master->loop();

        //master is working
        $this->assertTrue($master->working);

        //repo has forgotten the resumed state
        $this->assertFalse($repo->resumed());
    }

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
