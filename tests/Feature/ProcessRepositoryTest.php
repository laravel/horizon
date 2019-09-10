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
}
