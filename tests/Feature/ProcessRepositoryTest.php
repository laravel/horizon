<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\Contracts\ProcessRepository;
use Laravel\Horizon\Tests\IntegrationTest;

class ProcessRepositoryTest extends IntegrationTest
{
    public function test_expired_orphans_can_be_found()
    {
        $repo = resolve(ProcessRepository::class);

        $repo->orphaned('foo', [1, 2, 3, 4, 5, 6]);
        sleep(2);
        $repo->orphaned('foo', [1, 2, 3]);

        $orphans = $repo->orphanedFor('foo', 1);

        $this->assertEquals([1, 2, 3], $orphans);
    }

    public function test_orphans_can_be_deleted()
    {
        $repo = resolve(ProcessRepository::class);
        $repo->orphaned('foo', [1, 2, 3]);
        $repo->forgetOrphans('foo', [1, 2, 3]);
        $this->assertEquals([], $repo->allOrphans('foo'));
    }

    public function test_orphaned_lua_script_adds_new_processes()
    {
        $repo = resolve(ProcessRepository::class);

        $repo->orphaned('master', ['pid:1', 'pid:2', 'pid:3']);

        $orphans = $repo->allOrphans('master');

        $this->assertCount(3, $orphans);
        $this->assertArrayHasKey('pid:1', $orphans);
        $this->assertArrayHasKey('pid:2', $orphans);
        $this->assertArrayHasKey('pid:3', $orphans);
    }

    public function test_orphaned_lua_script_removes_stale_processes()
    {
        $repo = resolve(ProcessRepository::class);

        // First call records 5 processes
        $repo->orphaned('master', ['pid:1', 'pid:2', 'pid:3', 'pid:4', 'pid:5']);
        $this->assertCount(5, $repo->allOrphans('master'));

        // Second call with only 2 processes should remove the other 3
        $repo->orphaned('master', ['pid:2', 'pid:4']);

        $orphans = $repo->allOrphans('master');
        $this->assertCount(2, $orphans);
        $this->assertArrayHasKey('pid:2', $orphans);
        $this->assertArrayHasKey('pid:4', $orphans);
        $this->assertArrayNotHasKey('pid:1', $orphans);
        $this->assertArrayNotHasKey('pid:3', $orphans);
        $this->assertArrayNotHasKey('pid:5', $orphans);
    }

    public function test_orphaned_lua_script_preserves_original_timestamps()
    {
        $repo = resolve(ProcessRepository::class);

        // First call records processes with an initial timestamp
        $repo->orphaned('master', ['pid:1', 'pid:2']);
        $firstOrphans = $repo->allOrphans('master');
        $originalTimestamp = $firstOrphans['pid:1'];

        sleep(2);

        // Second call with the same processes should NOT update existing timestamps (hsetnx)
        $repo->orphaned('master', ['pid:1', 'pid:2']);
        $secondOrphans = $repo->allOrphans('master');

        $this->assertSame($originalTimestamp, $secondOrphans['pid:1']);
        $this->assertSame($firstOrphans['pid:2'], $secondOrphans['pid:2']);
    }

    public function test_orphaned_lua_script_handles_empty_process_list()
    {
        $repo = resolve(ProcessRepository::class);

        // First populate some orphans
        $repo->orphaned('master', ['pid:1', 'pid:2']);
        $this->assertCount(2, $repo->allOrphans('master'));

        // Calling with empty list should remove all existing entries
        $repo->orphaned('master', []);
        $this->assertCount(0, $repo->allOrphans('master'));
    }

    public function test_orphaned_lua_script_adds_new_and_removes_stale_atomically()
    {
        $repo = resolve(ProcessRepository::class);

        // Initial set of processes
        $repo->orphaned('master', ['pid:1', 'pid:2', 'pid:3']);

        sleep(1);

        // New call: pid:2 still orphaned, pid:1 and pid:3 recovered, pid:4 newly orphaned
        $repo->orphaned('master', ['pid:2', 'pid:4']);

        $orphans = $repo->allOrphans('master');

        // pid:1 and pid:3 should be removed
        $this->assertArrayNotHasKey('pid:1', $orphans);
        $this->assertArrayNotHasKey('pid:3', $orphans);

        // pid:2 should still exist with its original timestamp
        $this->assertArrayHasKey('pid:2', $orphans);

        // pid:4 should be newly added
        $this->assertArrayHasKey('pid:4', $orphans);

        $this->assertCount(2, $orphans);
    }
}
