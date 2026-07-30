<?php

namespace Laravel\Horizon\Tests\Controller;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Batch;
use Illuminate\Bus\BatchRepository;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Inertia\Testing\AssertableInertia;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\Tests\ControllerTest;
use Mockery;

class BatchPageControllerTest extends ControllerTest
{
    public function test_batch_page_exposes_cursor_metadata_for_inertia_scroll()
    {
        $batches = collect(range(1, 51))
            ->map(fn (int $number): object => (object) [
                'id' => sprintf('batch-%03d', 52 - $number),
                'name' => 'Batch '.$number,
            ])
            ->all();

        $repository = Mockery::mock(BatchRepository::class);
        $repository->shouldReceive('get')
            ->once()
            ->with(51, null)
            ->andReturn($batches);
        $this->app->instance(BatchRepository::class, $repository);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/batches', [
                'X-Inertia' => 'true',
                'X-Inertia-Version' => Horizon::inertiaVersion(),
                'X-Inertia-Partial-Component' => 'batches',
                'X-Inertia-Partial-Data' => 'batches',
            ])
            ->assertOk()
            ->assertJsonCount(50, 'props.batches.data')
            ->assertJsonPath('props.batches.data.0.id', 'batch-051')
            ->assertJsonPath('props.batches.data.49.id', 'batch-002')
            ->assertJsonPath('scrollProps.batches.pageName', 'before_id')
            ->assertJsonPath('scrollProps.batches.previousPage', null)
            ->assertJsonPath('scrollProps.batches.nextPage', 'batch-002')
            ->assertJsonPath('scrollProps.batches.currentPage', null)
            ->assertJsonPath('mergeProps.0', 'batches.data')
            ->assertJsonPath('matchPropsOn.0', 'batches.data.id');
    }

    public function test_batch_page_loads_the_next_cursor_without_repeating_the_boundary()
    {
        $repository = Mockery::mock(BatchRepository::class);
        $repository->shouldReceive('get')
            ->once()
            ->with(51, 'batch-002')
            ->andReturn([
                (object) [
                    'id' => 'batch-001',
                    'name' => 'Final batch',
                ],
            ]);
        $this->app->instance(BatchRepository::class, $repository);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/batches?before_id=batch-002', [
                'X-Inertia' => 'true',
                'X-Inertia-Version' => Horizon::inertiaVersion(),
                'X-Inertia-Partial-Component' => 'batches',
                'X-Inertia-Partial-Data' => 'batches',
            ])
            ->assertOk()
            ->assertJsonCount(1, 'props.batches.data')
            ->assertJsonPath('props.batches.data.0.id', 'batch-001')
            ->assertJsonPath('scrollProps.batches.nextPage', null)
            ->assertJsonPath('scrollProps.batches.currentPage', 'batch-002');
    }

    public function test_batch_detail_page_exposes_batch_and_failed_jobs()
    {
        $queues = Mockery::mock(QueueFactory::class);
        $repository = Mockery::mock(BatchRepository::class);
        $batch = new Batch(
            $queues,
            $repository,
            'batch-detail-1',
            'Import users',
            10,
            2,
            1,
            ['job-failed-1'],
            ['queue' => 'default', 'connection' => 'redis'],
            CarbonImmutable::parse('2026-07-29 12:00:00'),
            null,
            null,
        );
        $repository->shouldReceive('find')->once()->with('batch-detail-1')->andReturn($batch);
        $repository->shouldReceive('get')->with(51, null)->andReturn([]);
        $this->app->instance(BatchRepository::class, $repository);

        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(0);
        $jobs->shouldReceive('getJobs')
            ->once()
            ->with(['job-failed-1'])
            ->andReturn(collect([
                (object) [
                    'id' => 'job-failed-1',
                    'name' => 'App\\Jobs\\ImportUser',
                    'payload' => json_encode([
                        'displayName' => 'App\\Jobs\\ImportUser',
                        'attempts' => 1,
                    ]),
                    'failed_at' => 1000,
                    'reserved_at' => 990,
                ],
            ]));
        $this->app->instance(JobRepository::class, $jobs);

        $this->actingAs(new Fakes\User)
            ->get('/horizon/batches/batch-detail-1')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('batches/show', false)
                ->where('batch.id', 'batch-detail-1')
                ->where('batch.name', 'Import users')
                ->where('batch.totalJobs', 10)
                ->where('batch.failedJobs', 1)
                ->where('batch.failedJobAttempts', 1)
                ->where('failedJobs.0.id', 'job-failed-1')
                ->where('failedJobs.0.attempts', 1)
                ->where('failedJobsComplete', true)
                ->etc());
    }

    public function test_batch_detail_collapses_retry_lineages_and_clamps_failed_counters()
    {
        $queues = Mockery::mock(QueueFactory::class);
        $repository = Mockery::mock(BatchRepository::class);
        $batch = new Batch(
            $queues,
            $repository,
            'batch-retry-lineage',
            'Retry lineage batch',
            1,
            1,
            2,
            ['original-failed', 'retry-failed'],
            ['queue' => 'default', 'connection' => 'redis'],
            CarbonImmutable::parse('2026-07-29 12:00:00'),
            null,
            null,
        );
        $repository->shouldReceive('find')->once()->with('batch-retry-lineage')->andReturn($batch);
        $repository->shouldReceive('get')->with(51, null)->andReturn([]);
        $this->app->instance(BatchRepository::class, $repository);

        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(0);
        $jobs->shouldReceive('getJobs')
            ->once()
            ->with(['original-failed', 'retry-failed'])
            ->andReturn(collect([
                (object) [
                    'id' => 'original-failed',
                    'name' => 'App\\Jobs\\DemoFailingJob',
                    'payload' => json_encode([
                        'displayName' => 'App\\Jobs\\DemoFailingJob',
                        'attempts' => 1,
                    ]),
                    'retried_by' => json_encode([
                        ['id' => 'retry-failed', 'status' => 'failed'],
                    ]),
                    'failed_at' => 1000,
                    'reserved_at' => 990,
                ],
                (object) [
                    'id' => 'retry-failed',
                    'name' => 'App\\Jobs\\DemoFailingJob',
                    'payload' => json_encode([
                        'displayName' => 'App\\Jobs\\DemoFailingJob',
                        'attempts' => 1,
                        'retry_of' => 'original-failed',
                    ]),
                    'failed_at' => 2000,
                    'reserved_at' => 1990,
                ],
            ]));
        $this->app->instance(JobRepository::class, $jobs);

        $this->actingAs(new Fakes\User)
            ->get('/horizon/batches/batch-retry-lineage')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('batches/show', false)
                ->where('batch.totalJobs', 1)
                ->where('batch.pendingJobs', 0)
                ->where('batch.failedJobs', 1)
                ->where('batch.failedJobAttempts', 2)
                ->has('failedJobs', 1)
                ->where('failedJobs.0.id', 'retry-failed')
                ->where('failedJobs.0.attempts', 2)
                ->where('failedJobsComplete', true)
                ->etc());
    }

    public function test_batch_detail_page_renders_empty_state_for_unknown_batches()
    {
        $repository = Mockery::mock(BatchRepository::class);
        $repository->shouldReceive('find')->once()->with('missing')->andReturn(null);
        $repository->shouldReceive('get')->with(51, null)->andReturn([]);
        $this->app->instance(BatchRepository::class, $repository);

        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(0);
        $this->app->instance(JobRepository::class, $jobs);

        $this->actingAs(new Fakes\User)
            ->get('/horizon/batches/missing')
            ->assertNotFound()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('batches/show', false)
                ->where('batch', null)
                ->where('failedJobs', [])
                ->where('failedJobsComplete', true)
                ->etc());
    }

    public function test_batch_detail_page_returns_ok_for_missing_batches_on_inertia_visits()
    {
        $repository = Mockery::mock(BatchRepository::class);
        $repository->shouldReceive('find')->once()->with('missing')->andReturn(null);
        $repository->shouldReceive('get')->with(51, null)->andReturn([]);
        $this->app->instance(BatchRepository::class, $repository);

        $jobs = Mockery::mock(JobRepository::class);
        $jobs->shouldReceive('countPending')->andReturn(0);
        $jobs->shouldReceive('countCompleted')->andReturn(0);
        $jobs->shouldReceive('countSilenced')->andReturn(0);
        $jobs->shouldReceive('countFailed')->andReturn(0);
        $this->app->instance(JobRepository::class, $jobs);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/batches/missing', [
                'X-Inertia' => 'true',
                'X-Inertia-Version' => Horizon::inertiaVersion(),
            ])
            ->assertOk()
            ->assertJsonPath('component', 'batches/show')
            ->assertJsonPath('props.batch', null)
            ->assertJsonPath('props.failedJobs', [])
            ->assertJsonPath('props.failedJobsComplete', true);
    }
}
