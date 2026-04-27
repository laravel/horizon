<?php

namespace Laravel\Horizon\Tests\Database;

use Illuminate\Bus\BatchRepository;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Tests\Feature\Jobs\BatchableJob;

class DatabaseBatchTest extends DatabaseIntegrationTestCase
{
    public function test_batched_jobs_are_recorded_in_horizon_jobs_with_batch_id_in_payload()
    {
        $batch = Bus::batch([
            new BatchableJob,
            new BatchableJob,
        ])->dispatch();

        $this->assertSame(2, $this->recentJobs());

        foreach (DB::table('horizon_jobs')->pluck('payload') as $payload) {
            $this->assertStringContainsString($batch->id, $payload);
        }
    }

    public function test_batch_records_are_persisted_in_job_batches_table()
    {
        $batch = Bus::batch([new BatchableJob])->dispatch();

        $this->assertNotNull(resolve(BatchRepository::class)->find($batch->id));
        $this->assertSame(1, DB::table('job_batches')->where('id', $batch->id)->count());
    }

    public function test_batched_jobs_complete_and_decrement_pending_count()
    {
        $batch = Bus::batch([
            new BatchableJob,
            new BatchableJob,
        ])->dispatch();

        $this->work(2);

        $record = DB::table('job_batches')->where('id', $batch->id)->first();

        $this->assertSame(0, (int) $record->pending_jobs);
        $this->assertSame(2, (int) $record->total_jobs);
        $this->assertNotNull($record->finished_at);
    }
}
