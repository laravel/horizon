<?php

namespace Laravel\Horizon\Tests\Unit;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Batch;
use Illuminate\Bus\BatchRepository;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Laravel\Horizon\Batches\BatchFailedJobLineages;
use Laravel\Horizon\Batches\BatchPresentation;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Tests\UnitTest;
use Mockery;

class BatchPresentationTest extends UnitTest
{
    public function test_it_clamps_failed_jobs_and_exposes_raw_attempts()
    {
        $presentation = new BatchPresentation(
            Mockery::mock(JobRepository::class),
            new BatchFailedJobLineages,
        );

        $batch = $this->batch(
            totalJobs: 1,
            pendingJobs: 1,
            failedJobs: 2,
            failedJobIds: ['original', 'retry'],
        );

        $summary = $presentation->summary($batch);

        $this->assertSame(1, $summary['totalJobs']);
        $this->assertSame(0, $summary['pendingJobs']);
        $this->assertSame(1, $summary['failedJobs']);
        $this->assertSame(2, $summary['failedJobAttempts']);
    }

    public function test_it_returns_lineage_collapsed_failed_job_rows()
    {
        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('getJobs')
            ->once()
            ->with(['original', 'retry'])
            ->andReturn(collect([
                (object) [
                    'id' => 'original',
                    'name' => 'App\\Jobs\\Demo',
                    'payload' => json_encode([
                        'displayName' => 'App\\Jobs\\Demo',
                        'attempts' => 1,
                    ]),
                    'retried_by' => json_encode([
                        ['id' => 'retry', 'status' => 'failed'],
                    ]),
                    'failed_at' => 100,
                    'reserved_at' => 90,
                ],
                (object) [
                    'id' => 'retry',
                    'name' => 'App\\Jobs\\Demo',
                    'payload' => json_encode([
                        'displayName' => 'App\\Jobs\\Demo',
                        'attempts' => 1,
                        'retry_of' => 'original',
                    ]),
                    'retried_by' => null,
                    'failed_at' => 200,
                    'reserved_at' => 190,
                ],
            ]));

        $presentation = new BatchPresentation($jobs, new BatchFailedJobLineages);
        $batch = $this->batch(
            totalJobs: 1,
            pendingJobs: 1,
            failedJobs: 2,
            failedJobIds: ['original', 'retry'],
        );

        $failed = $presentation->failedJobs($batch);

        $this->assertTrue($failed['complete']);
        $this->assertCount(1, $failed['rows']);
        $this->assertSame('retry', $failed['rows'][0]['id']);
        $this->assertSame(2, $failed['rows'][0]['attempts']);
    }

    /**
     * @param  array<int, string>  $failedJobIds
     */
    private function batch(
        int $totalJobs,
        int $pendingJobs,
        int $failedJobs,
        array $failedJobIds,
    ): Batch {
        $queues = Mockery::mock(QueueFactory::class);
        $repository = Mockery::mock(BatchRepository::class);

        return new Batch(
            $queues,
            $repository,
            'batch-1',
            'Demo',
            $totalJobs,
            $pendingJobs,
            $failedJobs,
            $failedJobIds,
            [],
            CarbonImmutable::now(),
            null,
            null,
        );
    }
}
