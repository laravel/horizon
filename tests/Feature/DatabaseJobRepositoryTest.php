<?php

namespace Laravel\Horizon\Tests\Feature;

use Exception;
use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Enums\JobStatus;
use Laravel\Horizon\JobPayload;
use Laravel\Horizon\Repositories\DatabaseJobRepository;
use Laravel\Horizon\Tests\DatabaseIntegrationTest;
use Throwable;

class DatabaseJobRepositoryTest extends DatabaseIntegrationTest
{
    protected function payload(string $id, string $displayName = 'foo', array $extra = []): JobPayload
    {
        return new JobPayload(json_encode(array_merge([
            'id' => $id,
            'uuid' => $id,
            'displayName' => $displayName,
        ], $extra)));
    }

    protected function uuid(): string
    {
        return (string) Str::uuid();
    }

    public function test_repository_is_database_implementation()
    {
        $this->assertInstanceOf(
            DatabaseJobRepository::class,
            $this->app->make(JobRepository::class)
        );
    }

    public function test_pushed_job_appears_in_recent_and_pending_and_stores_metadata()
    {
        $repository = $this->app->make(JobRepository::class);
        $id = $this->uuid();

        $repository->pushed('database', 'default', $this->payload($id, 'App\\Jobs\\ExampleJob'));

        $this->assertSame(1, $repository->totalRecent());
        $this->assertSame(1, $repository->countRecent());
        $this->assertSame(1, $repository->countPending());

        $job = $repository->getJobs([$id])->first();

        $this->assertSame($id, $job->id);
        $this->assertSame('database', $job->connection);
        $this->assertSame('default', $job->queue);
        $this->assertSame('App\\Jobs\\ExampleJob', $job->name);
        $this->assertSame('pending', $job->status);
        $this->assertSame(0, $job->index);
    }

    public function test_reserved_then_released_restores_pending_status_with_delay()
    {
        $repository = $this->app->make(JobRepository::class);
        $id = $this->uuid();
        $payload = $this->payload($id);

        $repository->pushed('database', 'default', $payload);
        $repository->reserved('database', 'default', $payload);
        $repository->released('database', 'default', $payload, 60);

        $job = $repository->getJobs([$id])->first();

        $this->assertSame('pending', $job->status);
        $this->assertSame('60', $job->delay);
    }

    public function test_migrated_clears_delay_back_to_zero()
    {
        $repository = $this->app->make(JobRepository::class);
        $id = $this->uuid();
        $payload = $this->payload($id);

        $repository->pushed('database', 'default', $payload);
        $repository->reserved('database', 'default', $payload);
        $repository->released('database', 'default', $payload, 60);
        $repository->migrated('database', 'default', collect([$payload]));

        $job = $repository->getJobs([$id])->first();

        $this->assertSame('pending', $job->status);
        $this->assertSame('0', $job->delay);
    }

    public function test_find_failed_returns_the_failed_job()
    {
        $repository = $this->app->make(JobRepository::class);
        $id = $this->uuid();

        $repository->failed(new Exception('Failed Job'), 'database', 'default', $this->payload($id));

        $this->assertSame($id, $repository->findFailed($id)->id);
    }

    public function test_find_failed_returns_null_when_job_has_not_failed()
    {
        $repository = $this->app->make(JobRepository::class);
        $id = $this->uuid();

        $repository->pushed('database', 'default', $this->payload($id));

        $this->assertNull($repository->findFailed($id));
    }

    public function test_find_failed_returns_null_for_unknown_id()
    {
        $repository = $this->app->make(JobRepository::class);

        $this->assertNull($repository->findFailed($this->uuid()));
    }

    public function test_delete_failed_removes_the_job_and_its_references()
    {
        $repository = $this->app->make(JobRepository::class);
        $id = $this->uuid();

        $repository->failed(new Exception('Failed Job'), 'database', 'default', $this->payload($id));

        $this->assertSame(1, $repository->deleteFailed($id));
        $this->assertNull($repository->findFailed($id));
        $this->assertSame(0, $repository->totalFailed());
    }

    public function test_delete_failed_is_a_noop_for_non_failed_jobs()
    {
        $repository = $this->app->make(JobRepository::class);
        $id = $this->uuid();

        $repository->pushed('database', 'default', $this->payload($id));

        $this->assertSame(0, $repository->deleteFailed($id));
        $this->assertSame($id, $repository->getRecent()->first()->id);
    }

    public function test_failed_transitions_removes_from_pending_and_adds_to_failed_refs()
    {
        $repository = $this->app->make(JobRepository::class);
        $id = $this->uuid();
        $payload = $this->payload($id);

        $repository->pushed('database', 'default', $payload);
        $repository->failed(new Exception('Failed Job'), 'database', 'default', $payload);

        $this->assertSame(0, $repository->countPending());
        $this->assertSame(1, $repository->totalFailed());
        $this->assertSame(1, $repository->countRecentlyFailed());
    }

    public function test_completed_moves_job_from_pending_to_completed_refs()
    {
        $repository = $this->app->make(JobRepository::class);
        $id = $this->uuid();
        $payload = $this->payload($id);

        $repository->pushed('database', 'default', $payload);
        $repository->completed($payload);

        $this->assertSame(0, $repository->countPending());
        $this->assertSame(1, $repository->countCompleted());

        $job = $repository->getJobs([$id])->first();
        $this->assertSame('completed', $job->status);
    }

    public function test_completed_silenced_routes_to_silenced_refs()
    {
        $repository = $this->app->make(JobRepository::class);
        $id = $this->uuid();
        $payload = $this->payload($id);

        $repository->pushed('database', 'default', $payload);
        $repository->completed($payload, false, true);

        $this->assertSame(1, $repository->countSilenced());
        $this->assertSame(0, $repository->countCompleted());
    }

    public function test_remember_stores_monitored_record_for_a_completed_job()
    {
        $repository = $this->app->make(JobRepository::class);
        $id = $this->uuid();
        $payload = $this->payload($id);

        $repository->remember('database', 'default', $payload);

        $job = $repository->getJobs([$id])->first();
        $this->assertNotNull($job);
        $this->assertSame('completed', $job->status);

        $this->assertSame(
            1,
            \Laravel\Horizon\Models\HorizonJobReference::where('type', 'monitored')->count()
        );
    }

    public function test_purge_removes_pending_jobs_for_a_queue_and_preserves_completed_history()
    {
        $repository = $this->app->make(JobRepository::class);

        $ids = collect(range(1, 5))->map(fn () => $this->uuid())->all();

        foreach ($ids as $i => $id) {
            $repository->pushed('horizon', 'email-processing', $this->payload($id, 'Job'.($i + 1)));
        }

        $repository->completed($this->payload($ids[0], 'Job1'));
        $repository->completed($this->payload($ids[1], 'Job2'));

        $this->assertSame(3, $repository->purge('email-processing'));
        $this->assertSame(2, $repository->countRecent());
        $this->assertSame(0, $repository->countPending());
        $this->assertSame(2, $repository->countCompleted());

        $recent = $repository->getRecent();
        $this->assertNotNull($recent->firstWhere('id', $ids[0]));
        $this->assertNotNull($recent->firstWhere('id', $ids[1]));
        $this->assertCount(2, $repository->getJobs($ids));
    }

    public function test_store_retry_reference_appends_child_entries_to_parent()
    {
        $repository = $this->app->make(JobRepository::class);
        $parentId = $this->uuid();

        $repository->pushed('database', 'default', $this->payload($parentId));

        $repository->storeRetryReference($parentId, $retry1 = $this->uuid());
        $repository->storeRetryReference($parentId, $retry2 = $this->uuid());

        $job = $repository->getJobs([$parentId])->first();
        $retries = json_decode($job->retried_by, true);

        $this->assertCount(2, $retries);
        $this->assertSame($retry1, $retries[0]['id']);
        $this->assertSame($retry2, $retries[1]['id']);
        $this->assertSame('pending', $retries[0]['status']);
    }

    public function test_delete_monitored_extends_expiration()
    {
        $repository = $this->app->make(JobRepository::class);
        $id = $this->uuid();

        $repository->pushed('database', 'default', $this->payload($id));

        $repository->deleteMonitored([$id]);

        $job = \Laravel\Horizon\Models\HorizonJob::find($id);
        $this->assertNotNull($job);
        $this->assertTrue($job->expires_at->greaterThan(now()->addDays(6)));
    }

    public function test_pagination_uses_keyset_cursor_across_pages()
    {
        $repository = $this->app->make(JobRepository::class);

        $pushed = [];
        for ($i = 0; $i < 55; $i++) {
            $id = $this->uuid();
            $pushed[] = $id;
            $repository->pushed('database', 'default', $this->payload($id, 'Job'.$i));
            usleep(1000);
        }

        $firstPage = $repository->getRecent();
        $this->assertCount(50, $firstPage);

        $cursor = $firstPage->last()->index;
        $this->assertIsString($cursor);

        $secondPage = $repository->getRecent($cursor);
        $this->assertCount(5, $secondPage);

        $seen = $firstPage->pluck('id')->merge($secondPage->pluck('id'))->all();
        $this->assertCount(55, $seen);
        $this->assertSame(55, count(array_unique($seen)));
    }

    public function test_first_page_sentinels_return_all_rows()
    {
        $repository = $this->app->make(JobRepository::class);

        $ids = [];
        for ($i = 0; $i < 3; $i++) {
            $id = $this->uuid();
            $ids[] = $id;
            $repository->pushed('database', 'default', $this->payload($id, 'Job'.$i));
            $repository->completed($this->payload($id, 'Job'.$i));
            usleep(1000);
        }

        foreach ([null, '', -1, '-1', 0, '0'] as $sentinel) {
            $rows = $repository->getCompleted($sentinel);
            $this->assertCount(
                3,
                $rows,
                sprintf('Sentinel %s should return the first page in full', var_export($sentinel, true))
            );
        }
    }

    public function test_deep_pagination_returns_every_job_exactly_once()
    {
        $repository = $this->app->make(JobRepository::class);

        $pushed = [];
        for ($i = 0; $i < 250; $i++) {
            $id = $this->uuid();
            $pushed[] = $id;
            $repository->pushed('database', 'default', $this->payload($id, 'Job'.$i));
            usleep(1000);
        }

        $seen = [];
        $cursor = null;

        for ($page = 0; $page < 10; $page++) {
            $rows = $repository->getRecent($cursor);

            if ($rows->isEmpty()) {
                break;
            }

            foreach ($rows as $row) {
                $seen[] = $row->id;
            }

            $cursor = $rows->last()->index;
        }

        $this->assertCount(250, $seen);
        $this->assertSame(250, count(array_unique($seen)));
    }

    public function test_pagination_is_stable_across_rows_with_identical_score()
    {
        $repository = $this->app->make(JobRepository::class);

        $ids = [];
        for ($i = 0; $i < 3; $i++) {
            $id = $this->uuid();
            $ids[] = $id;
            $repository->pushed('database', 'default', $this->payload($id, 'Job'.$i));
        }

        \Laravel\Horizon\Models\HorizonJobReference::where('type', 'recent')
            ->update(['score' => 42]);

        $seen = [];
        $cursor = null;

        for ($page = 0; $page < 5; $page++) {
            $rows = $repository->getRecent($cursor);

            if ($rows->isEmpty()) {
                break;
            }

            foreach ($rows as $row) {
                $seen[] = $row->id;
            }

            $cursor = $rows->last()->index;

            if (count($seen) >= 3) {
                break;
            }
        }

        $this->assertCount(3, $seen);
        $this->assertSame(3, count(array_unique($seen)));
    }

    public function test_microtimes_are_stored_without_locale_comma()
    {
        $originalLocale = setlocale(LC_NUMERIC, 0);
        setlocale(LC_NUMERIC, 'fr_FR');

        try {
            $repository = $this->app->make(JobRepository::class);
            $id = $this->uuid();
            $payload = $this->payload($id);

            $repository->pushed('database', 'default', $payload);
            $repository->reserved('database', 'default', $payload);

            $result = $repository->getRecent()[0];

            $this->assertStringNotContainsString(',', $result->reserved_at);
        } catch (Exception|Throwable $e) {
            setlocale(LC_NUMERIC, $originalLocale);
            throw $e;
        }

        setlocale(LC_NUMERIC, $originalLocale);
    }

    public function test_trim_recent_jobs_drops_references_older_than_retention()
    {
        $repository = $this->app->make(JobRepository::class);
        $id = $this->uuid();

        $repository->pushed('database', 'default', $this->payload($id));

        \Laravel\Horizon\Models\HorizonJobReference::where('job_id', $id)
            ->update(['score' => 0]);

        $repository->trimRecentJobs();

        $this->assertSame(0, $repository->totalRecent());
        $this->assertSame(0, $repository->countPending());
    }

    public function test_pushed_is_idempotent_for_duplicate_events()
    {
        $repository = $this->app->make(JobRepository::class);
        $id = $this->uuid();
        $payload = $this->payload($id);

        $repository->pushed('database', 'default', $payload);
        $repository->pushed('database', 'default', $payload);

        $this->assertSame(1, $repository->totalRecent());
        $this->assertSame(1, $repository->countPending());
    }

    public function test_retried_by_round_trips_as_an_array_via_the_cast()
    {
        $id = $this->uuid();
        $this->app->make(JobRepository::class)
            ->pushed('database', 'default', $this->payload($id));

        $job = \Laravel\Horizon\Models\HorizonJob::find($id);
        $job->retried_by = [
            ['id' => 'retry-1', 'status' => JobStatus::Completed->value, 'retried_at' => 1],
        ];
        $job->save();

        $reloaded = \Laravel\Horizon\Models\HorizonJob::find($id);
        $this->assertIsArray($reloaded->retried_by);
        $this->assertSame('retry-1', $reloaded->retried_by[0]['id']);
    }

    public function test_update_retry_information_on_parent_round_trips_without_manual_json()
    {
        $repository = $this->app->make(JobRepository::class);

        $parentId = $this->uuid();
        $childId = $this->uuid();

        $repository->pushed('database', 'default', $this->payload($parentId));
        $repository->storeRetryReference($parentId, $childId);

        $retryPayload = $this->payload($childId, 'foo', ['retry_of' => $parentId]);
        $repository->completed($retryPayload);

        $parent = \Laravel\Horizon\Models\HorizonJob::find($parentId);

        $this->assertIsArray($parent->retried_by);
        $this->assertSame($childId, $parent->retried_by[0]['id']);
        $this->assertSame(
            JobStatus::Completed->value,
            $parent->retried_by[0]['status']
        );
    }

    public function test_reserved_and_released_are_noops_for_missing_jobs()
    {
        $repository = $this->app->make(JobRepository::class);
        $payload = $this->payload($this->uuid());

        $repository->reserved('database', 'default', $payload);
        $repository->released('database', 'default', $payload, 30);

        $this->assertSame(0, $repository->totalRecent());
    }

    public function test_trim_recent_jobs_deletes_all_expired_references_in_chunks()
    {
        $repository = $this->app->make(JobRepository::class);

        $rows = [];
        $now = \Carbon\CarbonImmutable::now();

        for ($i = 0; $i < 1_100; $i++) {
            $rows[] = [
                'type' => \Laravel\Horizon\Enums\JobReferenceType::Recent->value,
                'job_id' => $this->uuid(),
                'queue' => 'default',
                'score' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        \Laravel\Horizon\Models\HorizonJobReference::insert($rows);

        \Illuminate\Support\Facades\DB::enableQueryLog();

        $repository->trimRecentJobs();

        $deleteStatements = collect(\Illuminate\Support\Facades\DB::getQueryLog())
            ->filter(fn ($q) => str_starts_with(strtolower($q['query']), 'delete')
                && str_contains($q['query'], 'horizon_job_references'));

        \Illuminate\Support\Facades\DB::disableQueryLog();

        $this->assertSame(0, \Laravel\Horizon\Models\HorizonJobReference::count());
        $this->assertGreaterThanOrEqual(
            2,
            $deleteStatements->count(),
            'Expected chunked deletes to keep the lock window small.'
        );
    }

    public function test_purge_uses_chunked_deletes_on_references()
    {
        $repository = $this->app->make(JobRepository::class);

        $jobRows = [];
        $refRows = [];
        $now = \Carbon\CarbonImmutable::now();

        for ($i = 0; $i < 1_100; $i++) {
            $id = $this->uuid();
            $jobRows[] = [
                'id' => $id,
                'queue' => 'bulk',
                'status' => JobStatus::Pending->value,
                'payload' => '{}',
                'expires_at' => $now->addMinutes(10),
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $refRows[] = [
                'type' => \Laravel\Horizon\Enums\JobReferenceType::Pending->value,
                'job_id' => $id,
                'queue' => 'bulk',
                'score' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        \Laravel\Horizon\Models\HorizonJob::insert($jobRows);
        \Laravel\Horizon\Models\HorizonJobReference::insert($refRows);

        \Illuminate\Support\Facades\DB::enableQueryLog();

        $purged = $repository->purge('bulk');

        $refDeletes = collect(\Illuminate\Support\Facades\DB::getQueryLog())
            ->filter(fn ($q) => str_starts_with(strtolower($q['query']), 'delete')
                && str_contains($q['query'], 'horizon_job_references'));

        \Illuminate\Support\Facades\DB::disableQueryLog();

        $this->assertSame(1_100, $purged);
        $this->assertSame(0, \Laravel\Horizon\Models\HorizonJobReference::where('queue', 'bulk')->count());
        $this->assertGreaterThanOrEqual(
            2,
            $refDeletes->count(),
            'Expected reference deletions to be chunked across multiple statements.'
        );
    }

    public function test_pushed_is_last_write_wins_on_duplicate_ids()
    {
        $repository = $this->app->make(JobRepository::class);
        $id = $this->uuid();

        $repository->pushed('database', 'default', $this->payload($id, 'First'));
        $repository->pushed('database', 'emails', $this->payload($id, 'Second'));

        $job = \Laravel\Horizon\Models\HorizonJob::find($id);

        $this->assertSame('Second', $job->name);
        $this->assertSame('emails', $job->queue);
    }
}
