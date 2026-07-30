<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Bus\BatchRepository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Once;
use Laravel\Horizon\Batches\BatchRepositoryOverview;
use Laravel\Horizon\Dashboard\DashboardStats;
use Laravel\Horizon\Support\NavigationCounts;
use Laravel\Horizon\Tests\ControllerTest;
use Mockery;
use RuntimeException;
use Throwable;

class BatchRepositoryOverviewTest extends ControllerTest
{
    protected function tearDown(): void
    {
        Once::flush();

        parent::tearDown();
    }

    public function test_navigation_and_dashboard_share_one_batch_repository_page()
    {
        $batches = Mockery::mock(BatchRepository::class);
        $batches->shouldReceive('get')->once()->with(51, null)->andReturn([]);
        $this->app->instance(BatchRepository::class, $batches);

        $this->app->make(NavigationCounts::class)->get();
        $this->app->make(DashboardStats::class)->get();
        $this->app->make(BatchRepositoryOverview::class)->get();
    }

    public function test_unexpected_batch_repository_failure_is_reported_once_for_shared_overview()
    {
        $batches = Mockery::mock(BatchRepository::class);
        $batches->shouldReceive('get')->once()->with(51, null)
            ->andThrow(new RuntimeException('batch storage failure'));
        $this->app->instance(BatchRepository::class, $batches);

        $reported = 0;
        $handler = Mockery::mock(ExceptionHandler::class);
        $handler->shouldReceive('report')
            ->once()
            ->with(Mockery::on(function (Throwable $exception) use (&$reported): bool {
                $reported++;

                return $exception instanceof RuntimeException
                    && $exception->getMessage() === 'batch storage failure';
            }));
        $handler->shouldReceive('render')->zeroOrMoreTimes();
        $handler->shouldReceive('renderForConsole')->zeroOrMoreTimes();
        $handler->shouldReceive('shouldReport')->andReturn(true);
        $this->app->instance(ExceptionHandler::class, $handler);

        $overview = $this->app->make(BatchRepositoryOverview::class);

        $this->assertSame([
            'total' => null,
            'active' => null,
            'previews' => [],
        ], $overview->get());

        // Memoized: second consumer must not re-query or re-report.
        $this->assertSame([
            'total' => null,
            'active' => null,
            'previews' => [],
        ], $overview->get());

        $this->assertSame(1, $reported);
    }
}
