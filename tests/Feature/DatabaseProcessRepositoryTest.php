<?php

namespace Laravel\Horizon\Tests\Feature;

use Carbon\CarbonImmutable;
use Laravel\Horizon\Contracts\ProcessRepository;
use Laravel\Horizon\Repositories\DatabaseProcessRepository;
use Laravel\Horizon\Tests\DatabaseIntegrationTest;

class DatabaseProcessRepositoryTest extends DatabaseIntegrationTest
{
    protected function repo(): DatabaseProcessRepository
    {
        return $this->app->make(ProcessRepository::class);
    }

    public function test_orphaned_replaces_previous_entries_with_current_list()
    {
        $repo = $this->repo();

        $repo->orphaned('master-A', ['1', '2', '3', '4']);
        $repo->orphaned('master-A', ['1', '2']);

        $this->assertEqualsCanonicalizing([1, 2], array_keys($repo->allOrphans('master-A')));
    }

    public function test_orphaned_preserves_earlier_recorded_at_for_existing_ids()
    {
        $repo = $this->repo();

        CarbonImmutable::setTestNow(CarbonImmutable::now());

        $repo->orphaned('master-A', ['1', '2']);

        $initial = $repo->allOrphans('master-A');

        CarbonImmutable::setTestNow(CarbonImmutable::now()->addSeconds(10));

        $repo->orphaned('master-A', ['1', '2', '3']);

        $after = $repo->allOrphans('master-A');

        $this->assertSame($initial[1], $after[1]);
        $this->assertSame($initial[2], $after[2]);
        $this->assertGreaterThan($initial[1], $after[3]);

        CarbonImmutable::setTestNow();
    }

    public function test_orphaned_for_returns_process_ids_older_than_threshold()
    {
        $repo = $this->repo();

        CarbonImmutable::setTestNow(CarbonImmutable::now());

        $repo->orphaned('master-A', ['1', '2', '3']);

        CarbonImmutable::setTestNow(CarbonImmutable::now()->addSeconds(10));

        $orphans = $repo->orphanedFor('master-A', 5);

        $this->assertEqualsCanonicalizing(['1', '2', '3'], $orphans);

        CarbonImmutable::setTestNow();
    }

    public function test_orphaned_for_returns_empty_when_none_are_old_enough()
    {
        $repo = $this->repo();

        $repo->orphaned('master-A', ['1', '2']);

        $this->assertSame([], $repo->orphanedFor('master-A', 60));
    }

    public function test_forget_orphans_removes_specified_ids()
    {
        $repo = $this->repo();

        $repo->orphaned('master-A', ['1', '2', '3']);
        $repo->forgetOrphans('master-A', ['1', '3']);

        $this->assertEquals([2], array_keys($repo->allOrphans('master-A')));
    }

    public function test_orphaned_with_empty_list_clears_master_entries()
    {
        $repo = $this->repo();

        $repo->orphaned('master-A', ['1', '2']);
        $repo->orphaned('master-A', []);

        $this->assertSame([], $repo->allOrphans('master-A'));
    }

    public function test_orphans_are_isolated_per_master()
    {
        $repo = $this->repo();

        $repo->orphaned('master-A', ['1', '2']);
        $repo->orphaned('master-B', ['3', '4']);

        $this->assertEqualsCanonicalizing([1, 2], array_keys($repo->allOrphans('master-A')));
        $this->assertEqualsCanonicalizing([3, 4], array_keys($repo->allOrphans('master-B')));
    }
}
