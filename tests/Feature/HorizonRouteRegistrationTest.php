<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Laravel\Horizon\Http\Middleware\EnsureQueuePausingIsSupported;
use Laravel\Horizon\Http\Middleware\HandleInertiaRequests;
use Laravel\Horizon\Tests\ControllerTest;

class HorizonRouteRegistrationTest extends ControllerTest
{
    public function test_browser_and_api_route_contracts_are_registered()
    {
        $expected = [
            // Browser / Inertia pages...
            ['GET', 'horizon', 'horizon.index', 'Laravel\Horizon\Http\Controllers\DashboardController@index'],
            ['GET', 'horizon/dashboard', 'horizon.dashboard', 'Laravel\Horizon\Http\Controllers\DashboardController@index'],
            ['GET', 'horizon/jobs/pending', 'horizon.pending-jobs.page', 'Laravel\Horizon\Http\Controllers\JobPageController@pending'],
            ['GET', 'horizon/jobs/completed', 'horizon.completed-jobs.page', 'Laravel\Horizon\Http\Controllers\JobPageController@completed'],
            ['GET', 'horizon/jobs/failed', 'horizon.failed-jobs.page', 'Laravel\Horizon\Http\Controllers\JobPageController@failed'],
            ['GET', 'horizon/jobs/silenced', 'horizon.silenced-jobs.page', 'Laravel\Horizon\Http\Controllers\JobPageController@silenced'],
            ['GET', 'horizon/jobs/{type}/{id}', 'horizon.jobs.page.show', 'Laravel\Horizon\Http\Controllers\JobPageController@show'],
            ['GET', 'horizon/monitoring', 'horizon.monitoring.page', 'Laravel\Horizon\Http\Controllers\MonitoringPageController@index'],
            ['GET', 'horizon/monitoring/{tag}/jobs', 'horizon.monitoring-jobs.page', 'Laravel\Horizon\Http\Controllers\MonitoringPageController@jobs'],
            ['GET', 'horizon/monitoring/{tag}/failed', 'horizon.monitoring-failed.page', 'Laravel\Horizon\Http\Controllers\MonitoringPageController@failed'],
            ['GET', 'horizon/metrics/{type}', 'horizon.metrics.page', 'Laravel\Horizon\Http\Controllers\MetricsPageController@index'],
            ['GET', 'horizon/metrics/{type}/{name}', 'horizon.metrics.page.show', 'Laravel\Horizon\Http\Controllers\MetricController@show'],
            ['GET', 'horizon/batches', 'horizon.batches.page', 'Laravel\Horizon\Http\Controllers\BatchPageController@index'],
            ['GET', 'horizon/batches/{id}', 'horizon.batches.page.show', 'Laravel\Horizon\Http\Controllers\BatchPageController@show'],

            // Legacy browser redirects (unnamed)...
            ['GET', 'horizon/monitoring/{tag}', null, 'Laravel\Horizon\Http\Controllers\LegacyRedirectController@monitoringTag'],
            ['GET', 'horizon/metrics', null, 'Laravel\Horizon\Http\Controllers\LegacyRedirectController@metrics'],
            ['GET', 'horizon/failed', null, 'Laravel\Horizon\Http\Controllers\LegacyRedirectController@failedJobs'],
            ['GET', 'horizon/failed/{id}', null, 'Laravel\Horizon\Http\Controllers\LegacyRedirectController@failedJob'],

            // JSON API...
            ['GET', 'horizon/api/stats', 'horizon.stats.index', 'Laravel\Horizon\Http\Controllers\DashboardStatsController@index'],
            ['GET', 'horizon/api/workload', 'horizon.workload.index', 'Laravel\Horizon\Http\Controllers\WorkloadController@index'],
            ['POST', 'horizon/api/queues/{connection}/{queue}/pause', 'horizon.queues.pause.store', 'Laravel\Horizon\Http\Controllers\QueuePauseController@store'],
            ['DELETE', 'horizon/api/queues/{connection}/{queue}/pause', 'horizon.queues.pause.destroy', 'Laravel\Horizon\Http\Controllers\QueuePauseController@destroy'],
            ['GET', 'horizon/api/masters', 'horizon.masters.index', 'Laravel\Horizon\Http\Controllers\MasterSupervisorController@index'],
            ['GET', 'horizon/api/monitoring', 'horizon.monitoring.index', 'Laravel\Horizon\Http\Controllers\MonitoringController@index'],
            ['POST', 'horizon/api/monitoring', 'horizon.monitoring.store', 'Laravel\Horizon\Http\Controllers\MonitoringController@store'],
            ['GET', 'horizon/api/monitoring/{tag}', 'horizon.monitoring-tag.paginate', 'Laravel\Horizon\Http\Controllers\MonitoringController@paginate'],
            ['DELETE', 'horizon/api/monitoring/{tag}', 'horizon.monitoring-tag.destroy', 'Laravel\Horizon\Http\Controllers\MonitoringController@destroy'],
            ['GET', 'horizon/api/metrics/jobs', 'horizon.jobs-metrics.index', 'Laravel\Horizon\Http\Controllers\JobMetricsController@index'],
            ['GET', 'horizon/api/metrics/jobs/{id}', 'horizon.jobs-metrics.show', 'Laravel\Horizon\Http\Controllers\JobMetricsController@show'],
            ['GET', 'horizon/api/metrics/queues', 'horizon.queues-metrics.index', 'Laravel\Horizon\Http\Controllers\QueueMetricsController@index'],
            ['GET', 'horizon/api/metrics/queues/{id}', 'horizon.queues-metrics.show', 'Laravel\Horizon\Http\Controllers\QueueMetricsController@show'],
            ['GET', 'horizon/api/batches', 'horizon.jobs-batches.index', 'Laravel\Horizon\Http\Controllers\BatchesController@index'],
            ['GET', 'horizon/api/batches/{id}', 'horizon.jobs-batches.show', 'Laravel\Horizon\Http\Controllers\BatchesController@show'],
            ['POST', 'horizon/api/batches/retry/{id}', 'horizon.jobs-batches.retry', 'Laravel\Horizon\Http\Controllers\BatchesController@retry'],
            ['GET', 'horizon/api/jobs/pending', 'horizon.pending-jobs.index', 'Laravel\Horizon\Http\Controllers\PendingJobsController@index'],
            ['GET', 'horizon/api/jobs/completed', 'horizon.completed-jobs.index', 'Laravel\Horizon\Http\Controllers\CompletedJobsController@index'],
            ['GET', 'horizon/api/jobs/silenced', 'horizon.silenced-jobs.index', 'Laravel\Horizon\Http\Controllers\SilencedJobsController@index'],
            ['GET', 'horizon/api/jobs/failed', 'horizon.failed-jobs.index', 'Laravel\Horizon\Http\Controllers\FailedJobsController@index'],
            ['GET', 'horizon/api/jobs/failed/{id}', 'horizon.failed-jobs.show', 'Laravel\Horizon\Http\Controllers\FailedJobsController@show'],
            ['POST', 'horizon/api/jobs/retry/{id}', 'horizon.retry-jobs.show', 'Laravel\Horizon\Http\Controllers\RetryController@store'],
            ['GET', 'horizon/api/jobs/{id}', 'horizon.jobs.show', 'Laravel\Horizon\Http\Controllers\JobsController@show'],
        ];

        $actual = $this->horizonRoutes()->map(function ($route) {
            $methods = array_values(array_diff($route->methods(), ['HEAD']));
            sort($methods);

            return [
                implode('|', $methods),
                $route->uri(),
                $route->getName(),
                $route->getActionName(),
            ];
        })->sortBy(fn (array $row) => $row[1].'|'.$row[0].'|'.($row[2] ?? ''))->values()->all();

        $expectedNormalized = collect($expected)->map(function (array $row) {
            $methods = explode('|', $row[0]);
            sort($methods);

            return [implode('|', $methods), $row[1], $row[2], $row[3]];
        })->sortBy(fn (array $row) => $row[1].'|'.$row[0].'|'.($row[2] ?? ''))->values()->all();

        $this->assertSame($expectedNormalized, $actual);
    }

    public function test_browser_routes_include_handle_inertia_requests_middleware()
    {
        $browser = $this->horizonRoutes()->filter(function ($route) {
            return ! str_starts_with($route->uri(), 'horizon/api');
        });

        $this->assertNotEmpty($browser);

        foreach ($browser as $route) {
            $this->assertContains(
                HandleInertiaRequests::class,
                $route->gatherMiddleware(),
                sprintf('Expected HandleInertiaRequests on browser route [%s %s].', implode('|', $route->methods()), $route->uri())
            );
            $this->assertContains('horizon', $route->gatherMiddleware());
            $this->assertNotContains('api', $route->gatherMiddleware());
        }
    }

    public function test_api_routes_do_not_include_handle_inertia_requests_or_laravel_api_group()
    {
        $api = $this->horizonRoutes()->filter(function ($route) {
            return str_starts_with($route->uri(), 'horizon/api');
        });

        $this->assertNotEmpty($api);

        foreach ($api as $route) {
            $middleware = $route->gatherMiddleware();

            $this->assertNotContains(
                HandleInertiaRequests::class,
                $middleware,
                sprintf('Did not expect HandleInertiaRequests on API route [%s %s].', implode('|', $route->methods()), $route->uri())
            );
            $this->assertContains('horizon', $middleware);
            // Must retain Horizon/web stack semantics — not Laravel's stateless api group.
            $this->assertNotContains('api', $middleware);
        }
    }

    public function test_queue_pause_routes_retain_capability_middleware()
    {
        $store = Route::getRoutes()->getByName('horizon.queues.pause.store');
        $destroy = Route::getRoutes()->getByName('horizon.queues.pause.destroy');

        $this->assertNotNull($store);
        $this->assertNotNull($destroy);

        $this->assertContains(EnsureQueuePausingIsSupported::class, $store->gatherMiddleware());
        $this->assertContains(EnsureQueuePausingIsSupported::class, $destroy->gatherMiddleware());
    }

    public function test_job_page_show_and_monitoring_tag_constraints_are_preserved()
    {
        $jobShow = Route::getRoutes()->getByName('horizon.jobs.page.show');
        $metricsPage = Route::getRoutes()->getByName('horizon.metrics.page');
        $monitoringJobs = Route::getRoutes()->getByName('horizon.monitoring-jobs.page');
        $monitoringDestroy = Route::getRoutes()->getByName('horizon.monitoring-tag.destroy');
        $queuePauseStore = Route::getRoutes()->getByName('horizon.queues.pause.store');
        $queuePauseDestroy = Route::getRoutes()->getByName('horizon.queues.pause.destroy');

        $this->assertSame('pending|completed|silenced|failed', $jobShow->wheres['type'] ?? null);
        $this->assertSame('jobs|queues', $metricsPage->wheres['type'] ?? null);
        $this->assertSame('.*', $monitoringJobs->wheres['tag'] ?? null);
        $this->assertSame('.*', $monitoringDestroy->wheres['tag'] ?? null);
        $this->assertSame('.*', $queuePauseStore->wheres['queue'] ?? null);
        $this->assertSame('.*', $queuePauseDestroy->wheres['queue'] ?? null);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \Illuminate\Routing\Route>
     */
    private function horizonRoutes()
    {
        return collect(Route::getRoutes())->filter(function ($route) {
            $uri = $route->uri();

            return $uri === 'horizon' || str_starts_with($uri, 'horizon/');
        })->values();
    }
}
