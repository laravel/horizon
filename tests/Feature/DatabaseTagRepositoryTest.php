<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Models\HorizonTag;
use Laravel\Horizon\Repositories\DatabaseTagRepository;
use Laravel\Horizon\Tests\DatabaseIntegrationTest;

class DatabaseTagRepositoryTest extends DatabaseIntegrationTest
{
    protected function repo(): DatabaseTagRepository
    {
        return $this->app->make(TagRepository::class);
    }

    protected function uuid(): string
    {
        return (string) Str::uuid();
    }

    public function test_repository_is_database_implementation()
    {
        $this->assertInstanceOf(DatabaseTagRepository::class, $this->repo());
    }

    public function test_monitor_and_monitoring_roundtrip()
    {
        $repo = $this->repo();

        $repo->monitor('notifications');
        $repo->monitor('emails');

        $this->assertEquals(['emails', 'notifications'], $repo->monitoring());
    }

    public function test_monitor_is_idempotent()
    {
        $repo = $this->repo();

        $repo->monitor('notifications');
        $repo->monitor('notifications');

        $this->assertCount(1, $repo->monitoring());
    }

    public function test_stop_monitoring_removes_a_tag()
    {
        $repo = $this->repo();

        $repo->monitor('notifications');
        $repo->stopMonitoring('notifications');

        $this->assertSame([], $repo->monitoring());
    }

    public function test_monitored_intersects_input_with_registered_tags()
    {
        $repo = $this->repo();

        $repo->monitor('a');
        $repo->monitor('b');

        $this->assertEqualsCanonicalizing(
            ['a', 'b'],
            $repo->monitored(['a', 'b', 'c'])
        );
    }

    public function test_add_stores_job_tags_and_is_idempotent()
    {
        $repo = $this->repo();
        $id = $this->uuid();

        $repo->add($id, ['first', 'second']);
        $repo->add($id, ['first', 'second']);

        $this->assertSame(1, $repo->count('first'));
        $this->assertSame(1, $repo->count('second'));
    }

    public function test_count_returns_zero_for_unknown_tag()
    {
        $this->assertSame(0, $this->repo()->count('missing'));
    }

    public function test_add_temporary_sets_expiration()
    {
        $repo = $this->repo();
        $id = $this->uuid();

        $repo->addTemporary(10, $id, ['failed:first']);

        $row = HorizonTag::where('tag', 'failed:first')->where('job_id', $id)->first();

        $this->assertNotNull($row);
        $this->assertNotNull($row->expires_at);
    }

    public function test_jobs_returns_all_ids_for_a_tag()
    {
        $repo = $this->repo();
        [$a, $b] = [$this->uuid(), $this->uuid()];

        $repo->add($a, ['first']);
        $repo->add($b, ['first']);

        $this->assertEqualsCanonicalizing([$a, $b], $repo->jobs('first'));
    }

    public function test_paginate_returns_indexed_map_ordered_by_newest()
    {
        $repo = $this->repo();

        $ids = [];
        for ($i = 0; $i < 5; $i++) {
            $id = $this->uuid();
            $ids[] = $id;
            $repo->add($id, ['batch']);
            usleep(1000);
        }

        $page = $repo->paginate('batch', 0, 3);

        $this->assertSame([0, 1, 2], array_keys($page));
        $this->assertSame($ids[4], $page[0]); // newest first
        $this->assertSame($ids[3], $page[1]);
        $this->assertSame($ids[2], $page[2]);

        $nextPage = $repo->paginate('batch', 3, 3);

        $this->assertSame([3, 4], array_keys($nextPage));
    }

    public function test_forget_jobs_removes_specific_pairs()
    {
        $repo = $this->repo();
        [$a, $b] = [$this->uuid(), $this->uuid()];

        $repo->add($a, ['first', 'second']);
        $repo->add($b, ['first']);

        $repo->forgetJobs(['first'], [$a]);

        $this->assertSame(1, $repo->count('first'));
        $this->assertSame(1, $repo->count('second'));
        $this->assertSame([$b], $repo->jobs('first'));
    }

    public function test_forget_removes_all_entries_for_a_tag()
    {
        $repo = $this->repo();
        $id = $this->uuid();

        $repo->add($id, ['first', 'second']);
        $repo->forget('first');

        $this->assertSame(0, $repo->count('first'));
        $this->assertSame(1, $repo->count('second'));
    }
}
