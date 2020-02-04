<?php

use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    // Dashboard Routes...
    Route::get('/stats', 'DashboardStatsController@index')->name('horizon.stats.index');

    // Workload Routes...
    Route::get('/workload', 'WorkloadController@index')->name('horizon.workload.index');

    // Master Supervisor Routes...
    Route::get('/masters', 'MasterSupervisorController@index')->name('horizon.masters.index');

    // Monitoring Routes...
    Route::get('/monitoring', 'MonitoringController@index')->name('horizon.monitoring.index');
    Route::post('/monitoring', 'MonitoringController@store')->name('horizon.monitoring.store');
    Route::get('/monitoring/{tag}', 'MonitoringController@paginate')->name('horizon.monitoring-tag.paginate');
    Route::delete('/monitoring/{tag}', 'MonitoringController@destroy')->name('horizon.monitoring-tag.destroy');

    // Job Metric Routes...
    Route::get('/metrics/jobs', 'JobMetricsController@index')->name('horizon.jobs-metrics.index');
    Route::get('/metrics/jobs/{id}', 'JobMetricsController@show')->name('horizon.jobs-metrics.show');

    // Queue Metric Routes...
    Route::get('/metrics/queues', 'QueueMetricsController@index')->name('horizon.queues-metrics.index');
    Route::get('/metrics/queues/{id}', 'QueueMetricsController@show')->name('horizon.queues-metrics.show');

    // Job Routes...
    Route::get('/jobs/recent', 'RecentJobsController@index')->name('horizon.recent-jobs.index');
    Route::get('/jobs/recent/{id}', 'RecentJobsController@show')->name('horizon.recent-jobs.show');
    Route::get('/jobs/failed', 'FailedJobsController@index')->name('horizon.failed-jobs.index');
    Route::get('/jobs/failed/{id}', 'FailedJobsController@show')->name('horizon.failed-jobs.show');
    Route::post('/jobs/retry/{id}', 'RetryController@store')->name('horizon.retry-jobs.show');
});

// Catch-all Route...
Route::get('/{view?}', 'HomeController@index')->where('view', '(.*)')->name('horizon.index');
