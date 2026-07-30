<?php

namespace Laravel\Horizon\Tests\Unit;

use Illuminate\Bus\BatchRepository;
use Illuminate\Support\Once;
use Laravel\Horizon\Batches\BatchRepositoryOverview;
use Laravel\Horizon\Batches\DatabaseBatchCapability;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Support\NavigationCounts;
use Laravel\Horizon\Tests\UnitTest;
use Mockery;

class NavigationCountsTest extends UnitTest
{
    protected function tearDown(): void
    {
        Once::flush();

        parent::tearDown();
    }

    public function test_it_builds_the_shared_navigation_count_contract()
    {
        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('countPending')->once()->andReturn(5);
        $jobs->shouldReceive('countCompleted')->once()->andReturn(36);
        $jobs->shouldReceive('countSilenced')->once()->andReturn(3);
        $jobs->shouldReceive('countFailed')->once()->andReturn(6);

        $tags = Mockery::mock(TagRepository::class);
        $tags->shouldReceive('monitoring')->once()->andReturn(['alpha', 'beta']);

        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('measuredJobs')->once()->andReturn(['JobA', 'JobB']);
        $metrics->shouldReceive('measuredQueues')->once()->andReturn(['default']);

        $batches = Mockery::mock(BatchRepository::class);
        $batches->shouldReceive('get')->once()->with(51, null)->andReturn([
            (object) ['id' => 'batch-1'],
            (object) ['id' => 'batch-2'],
        ]);

        $overview = new BatchRepositoryOverview(
            $batches,
            new DatabaseBatchCapability($batches),
        );

        $counts = (new NavigationCounts($jobs, $tags, $metrics, $overview))->get();

        $this->assertSame([
            'monitoring' => 2,
            'metrics' => 3,
            'batches' => 2,
            'pending' => 5,
            'completed' => 36,
            'silenced' => 3,
            'failed' => 6,
        ], $counts);
    }

    public function test_batches_are_null_when_more_than_the_bounded_page_exists()
    {
        $jobs = $this->jobCounts();
        $tags = Mockery::mock(TagRepository::class);
        $tags->shouldReceive('monitoring')->andReturn([]);
        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('measuredJobs')->andReturn([]);
        $metrics->shouldReceive('measuredQueues')->andReturn([]);

        $batches = Mockery::mock(BatchRepository::class);
        $batches->shouldReceive('get')->once()->with(51, null)->andReturn(
            array_fill(0, 51, (object) ['id' => 'batch'])
        );

        $overview = new BatchRepositoryOverview(
            $batches,
            new DatabaseBatchCapability($batches),
        );

        $counts = (new NavigationCounts($jobs, $tags, $metrics, $overview))->get();

        $this->assertNull($counts['batches']);
    }

    public function test_batches_are_null_when_the_batch_repository_fails()
    {
        $jobs = $this->jobCounts();
        $tags = Mockery::mock(TagRepository::class);
        $tags->shouldReceive('monitoring')->andReturn([]);
        $metrics = Mockery::mock(MetricsRepository::class);
        $metrics->shouldReceive('measuredJobs')->andReturn([]);
        $metrics->shouldReceive('measuredQueues')->andReturn([]);

        $batches = Mockery::mock(BatchRepository::class);
        $batches->shouldReceive('get')->once()->with(51, null)
            ->andThrow(new \RuntimeException('missing table'));

        $overview = new BatchRepositoryOverview(
            $batches,
            new DatabaseBatchCapability($batches),
        );

        $counts = (new NavigationCounts($jobs, $tags, $metrics, $overview))->get();

        $this->assertNull($counts['batches']);
    }

    private function jobCounts()
    {
        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(0);

        return $jobs;
    }
}
