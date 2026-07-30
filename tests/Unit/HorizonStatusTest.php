<?php

namespace Laravel\Horizon\Tests\Unit;

use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Dashboard\HorizonStatus;
use Laravel\Horizon\Tests\UnitTest;
use Mockery;

class HorizonStatusTest extends UnitTest
{
    public function test_current_is_inactive_when_no_masters_are_present()
    {
        $status = $this->statusWithMasters([]);

        $this->assertSame('inactive', $status->current());
        $this->assertSame('inactive', $status->legacy());
        $this->assertSame(0, $status->pausedMasters());
    }

    public function test_current_is_running_when_every_master_is_running()
    {
        $status = $this->statusWithMasters([
            (object) ['status' => 'running'],
            (object) ['status' => 'running'],
        ]);

        $this->assertSame('running', $status->current());
        $this->assertSame('running', $status->legacy());
        $this->assertSame(0, $status->pausedMasters());
    }

    public function test_current_is_paused_when_every_master_is_paused()
    {
        $status = $this->statusWithMasters([
            (object) ['status' => 'paused'],
            (object) ['status' => 'paused'],
        ]);

        $this->assertSame('paused', $status->current());
        $this->assertSame('paused', $status->legacy());
        $this->assertSame(2, $status->pausedMasters());
    }

    public function test_current_is_partially_paused_when_masters_are_mixed()
    {
        $status = $this->statusWithMasters([
            (object) ['status' => 'running'],
            (object) ['status' => 'paused'],
        ]);

        $this->assertSame('partially_paused', $status->current());
        $this->assertSame('running', $status->legacy());
        $this->assertSame(1, $status->pausedMasters());
    }

    /**
     * @param  array<int, object>  $masters
     */
    private function statusWithMasters(array $masters): HorizonStatus
    {
        $repository = Mockery::mock(MasterSupervisorRepository::class);
        $repository->shouldReceive('all')->andReturn($masters);

        return new HorizonStatus($repository);
    }
}
