<?php

namespace Laravel\Horizon\Tests\Database;

use Laravel\Horizon\Contracts\ProcessRepository;

class DatabaseProcessRepositoryTest extends DatabaseTestCase
{
    public function test_expired_orphans_can_be_found()
    {
        $repo = resolve(ProcessRepository::class);

        $repo->orphaned('foo', [1, 2, 3, 4, 5, 6]);
        sleep(2);
        $repo->orphaned('foo', [1, 2, 3]);

        $orphans = $repo->orphanedFor('foo', 1);

        $this->assertEquals(['1', '2', '3'], $orphans);
    }

    public function test_orphans_can_be_deleted()
    {
        $repo = resolve(ProcessRepository::class);

        $repo->orphaned('foo', [1, 2, 3]);
        $repo->forgetOrphans('foo', [1, 2, 3]);

        $this->assertEquals([], $repo->allOrphans('foo'));
    }

    public function test_all_orphans_returns_process_ids_with_recorded_times()
    {
        $repo = resolve(ProcessRepository::class);

        $repo->orphaned('foo', [1, 2]);

        $orphans = $repo->allOrphans('foo');

        $this->assertArrayHasKey('1', $orphans);
        $this->assertArrayHasKey('2', $orphans);
    }
}
