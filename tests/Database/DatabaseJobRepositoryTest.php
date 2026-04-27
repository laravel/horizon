<?php

namespace Laravel\Horizon\Tests\Database;

use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\JobPayload;

class DatabaseJobRepositoryTest extends DatabaseTestCase
{
    public function test_it_can_find_a_failed_job_by_its_id()
    {
        $repository = $this->app->make(JobRepository::class);
        $payload = new JobPayload(json_encode(['id' => 1, 'displayName' => 'foo']));

        $repository->failed(new Exception('Failed Job'), 'database', 'default', $payload);

        $this->assertSame('1', $repository->findFailed(1)->id);
    }

    public function test_it_will_not_find_a_failed_job_if_the_job_has_not_failed()
    {
        $repository = $this->app->make(JobRepository::class);
        $payload = new JobPayload(json_encode(['id' => 1, 'displayName' => 'foo']));

        $repository->pushed('database', 'default', $payload);

        $this->assertNull($repository->findFailed(1));
    }

    public function test_it_saves_microseconds_as_a_float_and_disregards_the_locale()
    {
        $originalLocale = setlocale(LC_NUMERIC, 0);

        setlocale(LC_NUMERIC, 'fr_FR');

        try {
            $repository = $this->app->make(JobRepository::class);
            $payload = new JobPayload(json_encode(['id' => 1, 'displayName' => 'foo']));

            $repository->pushed('database', 'default', $payload);
            $repository->reserved('database', 'default', $payload);

            $result = $repository->getRecent()[0];

            $this->assertStringNotContainsString(',', (string) $result->reserved_at);
        } finally {
            setlocale(LC_NUMERIC, $originalLocale);
        }
    }

    public function test_it_removes_recent_jobs_when_queue_is_purged()
    {
        $repository = $this->app->make(JobRepository::class);

        $repository->pushed('horizon', 'email-processing', new JobPayload(json_encode(['id' => 1, 'displayName' => 'first'])));
        $repository->pushed('horizon', 'email-processing', new JobPayload(json_encode(['id' => 2, 'displayName' => 'second'])));
        $repository->pushed('horizon', 'email-processing', new JobPayload(json_encode(['id' => 3, 'displayName' => 'third'])));
        $repository->pushed('horizon', 'email-processing', new JobPayload(json_encode(['id' => 4, 'displayName' => 'fourth'])));
        $repository->pushed('horizon', 'email-processing', new JobPayload(json_encode(['id' => 5, 'displayName' => 'fifth'])));

        $repository->completed(new JobPayload(json_encode(['id' => 1, 'displayName' => 'first'])));
        $repository->completed(new JobPayload(json_encode(['id' => 2, 'displayName' => 'second'])));

        $this->assertEquals(3, $repository->purge('email-processing'));
        $this->assertEquals(2, $repository->countRecent());
        $this->assertEquals(0, $repository->countPending());
        $this->assertEquals(2, $repository->countCompleted());

        $recent = collect($repository->getRecent());
        $this->assertNotNull($recent->firstWhere('id', '1'));
        $this->assertNotNull($recent->firstWhere('id', '2'));
        $this->assertCount(2, $repository->getJobs([1, 2, 3, 4, 5]));
    }

    public function test_it_will_delete_a_failed_job()
    {
        $repository = $this->app->make(JobRepository::class);
        $payload = new JobPayload(json_encode(['id' => 1, 'displayName' => 'foo']));

        $repository->failed(new Exception('Failed Job'), 'database', 'default', $payload);

        $result = $repository->deleteFailed(1);

        $this->assertSame(1, $result);
        $this->assertNull($repository->findFailed(1));
    }

    public function test_it_will_not_delete_a_job_if_the_job_has_not_failed()
    {
        $repository = $this->app->make(JobRepository::class);
        $payload = new JobPayload(json_encode(['id' => 1, 'displayName' => 'foo']));

        $repository->pushed('database', 'default', $payload);

        $result = $repository->deleteFailed(1);

        $this->assertSame(0, $result);
        $this->assertSame('1', $repository->getRecent()[0]->id);
    }

    public function test_it_assigns_unique_ids_for_new_jobs()
    {
        $repository = $this->app->make(JobRepository::class);

        $first = $repository->nextJobId();
        $second = $repository->nextJobId();

        $this->assertNotSame($first, $second);
    }

    public function test_it_can_count_recent_failed_and_pending_jobs()
    {
        $repository = $this->app->make(JobRepository::class);

        $repository->pushed('database', 'default', new JobPayload(json_encode(['id' => 1, 'displayName' => 'foo'])));
        $repository->pushed('database', 'default', new JobPayload(json_encode(['id' => 2, 'displayName' => 'foo'])));
        $repository->failed(new Exception('Failed Job'), 'database', 'default', new JobPayload(json_encode(['id' => 3, 'displayName' => 'foo'])));

        $this->assertSame(3, $repository->totalRecent());
        $this->assertSame(1, $repository->totalFailed());
        $this->assertSame(2, $repository->countPending());
        $this->assertSame(1, $repository->countRecentlyFailed());
    }

    public function test_it_records_retry_references_on_the_original_job()
    {
        $repository = $this->app->make(JobRepository::class);

        $repository->pushed('database', 'default', new JobPayload(json_encode(['id' => 1, 'displayName' => 'foo'])));
        $repository->storeRetryReference(1, 'retry-id');

        $job = $repository->getJobs([1])->first();

        $retries = json_decode($job->retried_by, true);
        $this->assertSame('retry-id', $retries[0]['id']);
        $this->assertSame('pending', $retries[0]['status']);
    }

    public function test_silenced_jobs_are_marked_with_silenced_status_and_counted()
    {
        $repository = $this->app->make(JobRepository::class);
        $payload = new JobPayload(json_encode(['id' => 1, 'displayName' => 'foo']));

        $repository->pushed('database', 'default', $payload);
        $repository->completed($payload, false, true);

        $this->assertSame(1, $repository->countSilenced());
        $this->assertSame('silenced', DB::table('horizon_jobs')->where('id', 1)->value('status'));
    }

    public function test_recent_jobs_can_be_trimmed()
    {
        $repository = $this->app->make(JobRepository::class);

        $repository->pushed('database', 'default', new JobPayload(json_encode(['id' => 1, 'displayName' => 'old'])));
        $repository->pushed('database', 'default', new JobPayload(json_encode(['id' => 2, 'displayName' => 'fresh'])));

        DB::table('horizon_jobs')->where('id', 1)->update([
            'created_at' => CarbonImmutable::now()->subHours(2)->getTimestamp(),
        ]);

        $repository->trimRecentJobs();

        $this->assertNull(DB::table('horizon_jobs')->where('id', 1)->first());
        $this->assertNotNull(DB::table('horizon_jobs')->where('id', 2)->first());
    }

    public function test_failed_jobs_can_be_trimmed()
    {
        $repository = $this->app->make(JobRepository::class);

        $repository->failed(new Exception('Old'), 'database', 'default', new JobPayload(json_encode(['id' => 1, 'displayName' => 'old'])));
        $repository->failed(new Exception('Fresh'), 'database', 'default', new JobPayload(json_encode(['id' => 2, 'displayName' => 'fresh'])));

        DB::table('horizon_jobs')->where('id', 1)->update([
            'failed_at' => CarbonImmutable::now()->subWeeks(2)->getTimestamp(),
        ]);

        $repository->trimFailedJobs();

        $this->assertNull(DB::table('horizon_jobs')->where('id', 1)->first());
        $this->assertNotNull(DB::table('horizon_jobs')->where('id', 2)->first());
    }

    public function test_monitored_jobs_can_be_trimmed()
    {
        $repository = $this->app->make(JobRepository::class);

        $repository->remember('database', 'default', new JobPayload(json_encode(['id' => 1, 'displayName' => 'old'])));
        $repository->remember('database', 'default', new JobPayload(json_encode(['id' => 2, 'displayName' => 'fresh'])));

        DB::table('horizon_jobs')->where('id', 1)->update([
            'completed_at' => CarbonImmutable::now()->subWeeks(2)->getTimestamp(),
        ]);

        $repository->trimMonitoredJobs();

        $this->assertNull(DB::table('horizon_jobs')->where('id', 1)->first());
        $this->assertNotNull(DB::table('horizon_jobs')->where('id', 2)->first());
    }
}
