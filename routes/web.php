<?php

use Illuminate\Support\Facades\Route;
use Laravel\Horizon\Http\Middleware\EnsureQueuePausingIsSupported;
use Laravel\Horizon\Http\Middleware\HandleInertiaRequests;

Route::middleware(HandleInertiaRequests::class)->group(function () {
    Route::get('/', 'DashboardController@index')->name('horizon.index');
    Route::get('/dashboard', 'DashboardController@index')->name('horizon.dashboard');
    Route::get('/jobs/pending', 'JobPageController@pending')->name('horizon.pending-jobs.page');
    Route::get('/jobs/completed', 'JobPageController@completed')->name('horizon.completed-jobs.page');
    Route::get('/jobs/failed', 'JobPageController@failed')->name('horizon.failed-jobs.page');
    Route::get('/jobs/silenced', 'JobPageController@silenced')->name('horizon.silenced-jobs.page');
    Route::get('/jobs/{type}/{id}', 'JobPageController@show')
        ->where('type', 'pending|completed|silenced|failed')
        ->name('horizon.jobs.page.show');
    Route::get('/monitoring', 'MonitoringPageController@index')->name('horizon.monitoring.page');
    Route::get('/monitoring/{tag}/jobs', 'MonitoringPageController@jobs')
        ->where('tag', '.*')
        ->name('horizon.monitoring-jobs.page');
    Route::get('/monitoring/{tag}/failed', 'MonitoringPageController@failed')
        ->where('tag', '.*')
        ->name('horizon.monitoring-failed.page');
    // Upstream Vue-router: /monitoring/:tag (parent) → Inertia jobs tab.
    Route::get('/monitoring/{tag}', 'LegacyRedirectController@monitoringTag')
        ->where('tag', '.*');
    // Upstream Vue-router: /metrics → /metrics/jobs.
    Route::get('/metrics', 'LegacyRedirectController@metrics');
    Route::get('/metrics/{type}', 'MetricsPageController@index')
        ->where('type', 'jobs|queues')
        ->name('horizon.metrics.page');
    Route::get('/metrics/{type}/{name}', 'MetricController@show')
        ->where('type', 'jobs|queues')
        ->where('name', '.+')
        ->name('horizon.metrics.page.show');
    Route::get('/batches', 'BatchPageController@index')->name('horizon.batches.page');
    Route::get('/batches/{id}', 'BatchPageController@show')->name('horizon.batches.page.show');
    // Upstream Vue-router: /failed and /failed/:jobId.
    Route::get('/failed', 'LegacyRedirectController@failedJobs');
    Route::get('/failed/{id}', 'LegacyRedirectController@failedJob');
});

Route::prefix('api')->group(function () {
    // Dashboard Routes...
    Route::get('/stats', 'DashboardStatsController@index')->name('horizon.stats.index');

    // Workload Routes...
    Route::get('/workload', 'WorkloadController@index')->name('horizon.workload.index');

    // Queue controls...
    Route::post('/queues/{connection}/{queue}/pause', 'QueuePauseController@store')
        ->middleware(EnsureQueuePausingIsSupported::class)
        ->name('horizon.queues.pause.store');
    Route::delete('/queues/{connection}/{queue}/pause', 'QueuePauseController@destroy')
        ->middleware(EnsureQueuePausingIsSupported::class)
        ->name('horizon.queues.pause.destroy');

    // Master Supervisor Routes...
    Route::get('/masters', 'MasterSupervisorController@index')->name('horizon.masters.index');

    // Monitoring Routes...
    Route::get('/monitoring', 'MonitoringController@index')->name('horizon.monitoring.index');
    Route::post('/monitoring', 'MonitoringController@store')->name('horizon.monitoring.store');
    Route::get('/monitoring/{tag}', 'MonitoringController@paginate')->name('horizon.monitoring-tag.paginate');
    Route::delete('/monitoring/{tag}', 'MonitoringController@destroy')
        ->name('horizon.monitoring-tag.destroy')
        ->where('tag', '.*');

    // Job Metric Routes...
    Route::get('/metrics/jobs', 'JobMetricsController@index')->name('horizon.jobs-metrics.index');
    Route::get('/metrics/jobs/{id}', 'JobMetricsController@show')->name('horizon.jobs-metrics.show');

    // Queue Metric Routes...
    Route::get('/metrics/queues', 'QueueMetricsController@index')->name('horizon.queues-metrics.index');
    Route::get('/metrics/queues/{id}', 'QueueMetricsController@show')->name('horizon.queues-metrics.show');

    // Batches Routes...
    Route::get('/batches', 'BatchesController@index')->name('horizon.jobs-batches.index');
    Route::get('/batches/{id}', 'BatchesController@show')->name('horizon.jobs-batches.show');
    Route::post('/batches/retry/{id}', 'BatchesController@retry')->name('horizon.jobs-batches.retry');

    // Job Routes...
    Route::get('/jobs/pending', 'PendingJobsController@index')->name('horizon.pending-jobs.index');
    Route::get('/jobs/completed', 'CompletedJobsController@index')->name('horizon.completed-jobs.index');
    Route::get('/jobs/silenced', 'SilencedJobsController@index')->name('horizon.silenced-jobs.index');
    Route::get('/jobs/failed', 'FailedJobsController@index')->name('horizon.failed-jobs.index');
    Route::get('/jobs/failed/{id}', 'FailedJobsController@show')->name('horizon.failed-jobs.show');
    Route::post('/jobs/retry/{id}', 'RetryController@store')->name('horizon.retry-jobs.show');
    Route::get('/jobs/{id}', 'JobsController@show')->name('horizon.jobs.show');
});
