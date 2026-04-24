<?php

namespace Laravel\Horizon\Tests\Feature\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Laravel\Horizon\Enums\JobStatus;
use Laravel\Horizon\Models\HorizonJob;
use Laravel\Horizon\Models\HorizonJobReference;
use Laravel\Horizon\Tests\DatabaseIntegrationTest;

class RecoverStaleJobsCommandTest extends DatabaseIntegrationTest
{
    public function test_stale_reserved_job_transitions_to_failed_with_synthetic_exception()
    {
        config(['queue.connections.database.retry_after' => 60]);

        $id = (string) Str::uuid();

        HorizonJob::create([
            'id' => $id,
            'connection' => 'database',
            'queue' => 'default',
            'name' => 'App\\Jobs\\StuckJob',
            'status' => JobStatus::Reserved,
            'payload' => '{}',
            'reserved_at' => CarbonImmutable::now()->subMinutes(5),
            'expires_at' => CarbonImmutable::now()->addMinutes(10),
        ]);

        $this->artisan('horizon:recover-stale')->assertSuccessful();

        $recovered = HorizonJob::find($id);
        $this->assertSame(JobStatus::Failed, $recovered->status);
        $this->assertNotNull($recovered->failed_at);
        $this->assertStringContainsString('JobLostException', (string) $recovered->exception);

        $this->assertSame(1, HorizonJobReference::where('type', 'failed')->count());
        $this->assertSame(1, HorizonJobReference::where('type', 'recent_failed')->count());
    }

    public function test_fresh_reserved_job_inside_retry_window_is_not_touched()
    {
        config(['queue.connections.database.retry_after' => 90]);

        $id = (string) Str::uuid();

        HorizonJob::create([
            'id' => $id,
            'connection' => 'database',
            'queue' => 'default',
            'name' => 'App\\Jobs\\FreshJob',
            'status' => JobStatus::Reserved,
            'payload' => '{}',
            'reserved_at' => CarbonImmutable::now()->subSeconds(30),
            'expires_at' => CarbonImmutable::now()->addMinutes(10),
        ]);

        $this->artisan('horizon:recover-stale')->assertSuccessful();

        $this->assertSame(JobStatus::Reserved, HorizonJob::find($id)->status);
    }

    public function test_retry_after_is_resolved_per_connection()
    {
        config([
            'queue.connections.fast' => [
                'driver' => 'database',
                'table' => 'jobs',
                'queue' => 'default',
                'retry_after' => 10,
            ],
            'queue.connections.slow' => [
                'driver' => 'database',
                'table' => 'jobs',
                'queue' => 'default',
                'retry_after' => 3_600,
            ],
        ]);

        $fastId = (string) Str::uuid();
        $slowId = (string) Str::uuid();

        HorizonJob::create([
            'id' => $fastId,
            'connection' => 'fast',
            'queue' => 'default',
            'name' => 'FastJob',
            'status' => JobStatus::Reserved,
            'payload' => '{}',
            'reserved_at' => CarbonImmutable::now()->subSeconds(30),
            'expires_at' => CarbonImmutable::now()->addMinutes(10),
        ]);

        HorizonJob::create([
            'id' => $slowId,
            'connection' => 'slow',
            'queue' => 'default',
            'name' => 'SlowJob',
            'status' => JobStatus::Reserved,
            'payload' => '{}',
            'reserved_at' => CarbonImmutable::now()->subSeconds(30),
            'expires_at' => CarbonImmutable::now()->addMinutes(10),
        ]);

        $this->artisan('horizon:recover-stale')->assertSuccessful();

        $this->assertSame(JobStatus::Failed, HorizonJob::find($fastId)->status);
        $this->assertSame(JobStatus::Reserved, HorizonJob::find($slowId)->status);
    }

}
