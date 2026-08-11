<?php

namespace Laravel\Horizon\Tests\Unit;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Batch;
use Illuminate\Bus\BatchRepository;
use Illuminate\Bus\DatabaseBatchRepository;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Once;
use Laravel\Horizon\Batches\BatchRepositoryOverview;
use Laravel\Horizon\Batches\DatabaseBatchCapability;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Support\NavigationCounts;
use Laravel\Horizon\Tests\UnitTest;
use Mockery;
use RuntimeException;

class BatchRepositoryOverviewTest extends UnitTest
{
    protected function tearDown(): void
    {
        Once::flush();

        parent::tearDown();
    }

    public function test_custom_batch_repository_is_queried_when_available()
    {
        $repository = Mockery::mock(BatchRepository::class);
        $repository->shouldReceive('get')->once()->with(51, null)->andReturn([
            $this->activeBatch($repository, 'batch-1', 'Import', 10, 4),
            $this->finishedBatch($repository, 'batch-2', 'Done', 5),
        ]);

        $overview = (new BatchRepositoryOverview(
            $repository,
            new DatabaseBatchCapability($repository),
        ))->get();

        $this->assertSame(2, $overview['total']);
        $this->assertSame(1, $overview['active']);
        $this->assertSame([
            [
                'id' => 'batch-1',
                'name' => 'Import',
                'progress' => 60,
            ],
        ], $overview['previews']);
    }

    public function test_missing_database_batch_table_degrades_silently_without_querying()
    {
        $schema = Mockery::mock(Builder::class);
        $schema->shouldReceive('hasTable')->once()->with('job_batches')->andReturn(false);

        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getSchemaBuilder')->once()->andReturn($schema);

        $repository = Mockery::mock(DatabaseBatchRepository::class);
        $repository->shouldReceive('getConnection')->once()->andReturn($connection);
        $repository->shouldNotReceive('get');

        $overview = (new BatchRepositoryOverview(
            $repository,
            new DatabaseBatchCapability($repository),
        ))->get();

        $this->assertSame([
            'total' => null,
            'active' => null,
            'previews' => [],
        ], $overview);
    }

    public function test_unexpected_repository_failure_returns_unavailable_fallback()
    {
        $repository = Mockery::mock(BatchRepository::class);
        $repository->shouldReceive('get')->once()->with(51, null)
            ->andThrow(new RuntimeException('connection refused'));

        $overview = (new BatchRepositoryOverview(
            $repository,
            new DatabaseBatchCapability($repository),
        ))->get();

        $this->assertSame([
            'total' => null,
            'active' => null,
            'previews' => [],
        ], $overview);
    }

    public function test_shared_overview_is_memoized_for_navigation_and_dashboard_consumers()
    {
        $repository = Mockery::mock(BatchRepository::class);
        $repository->shouldReceive('get')->once()->with(51, null)->andReturn([
            $this->activeBatch($repository, 'batch-1', 'Active', 8, 2),
        ]);

        $overview = new BatchRepositoryOverview(
            $repository,
            new DatabaseBatchCapability($repository),
        );

        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('countPending')->andReturn(1);
        $jobs->shouldReceive('countCompleted')->andReturn(2);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(3);

        $tags = Mockery::mock(TagRepository::class);
        $tags->shouldReceive('monitoring')->andReturn([]);

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('measuredJobs')->andReturn([]);
        $metrics->shouldReceive('measuredQueues')->andReturn([]);

        $navigation = (new NavigationCounts($jobs, $tags, $metrics, $overview))->get();
        $dashboardBatches = $overview->get();

        $this->assertSame(1, $navigation['batches']);
        $this->assertSame(1, $dashboardBatches['total']);
        $this->assertSame(1, $dashboardBatches['active']);
        $this->assertCount(1, $dashboardBatches['previews']);
    }

    public function test_inexact_page_returns_null_total_and_active_with_previews()
    {
        $repository = Mockery::mock(BatchRepository::class);
        $retained = [];

        for ($i = 0; $i < 51; $i++) {
            $retained[] = $this->activeBatch($repository, "batch-{$i}", "Batch {$i}", 10, 1);
        }

        $repository->shouldReceive('get')->once()->with(51, null)->andReturn($retained);

        $overview = (new BatchRepositoryOverview(
            $repository,
            new DatabaseBatchCapability($repository),
        ))->get();

        $this->assertNull($overview['total']);
        $this->assertNull($overview['active']);
        $this->assertCount(3, $overview['previews']);
        $this->assertSame('batch-0', $overview['previews'][0]['id']);
    }

    private function activeBatch(
        BatchRepository $repository,
        string $id,
        string $name,
        int $total,
        int $pending,
    ): Batch {
        $queues = Mockery::mock(QueueFactory::class);

        return new Batch(
            $queues,
            $repository,
            $id,
            $name,
            $total,
            $pending,
            0,
            [],
            [],
            CarbonImmutable::now(),
        );
    }

    private function finishedBatch(
        BatchRepository $repository,
        string $id,
        string $name,
        int $total,
    ): Batch {
        $queues = Mockery::mock(QueueFactory::class);

        return new Batch(
            $queues,
            $repository,
            $id,
            $name,
            $total,
            0,
            0,
            [],
            [],
            CarbonImmutable::now(),
            null,
            CarbonImmutable::now(),
        );
    }
}
