<?php

use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    // Dashboard Routes...
    Route::get('/stats', '\Laravel\Horizon\Http\Controllers\DashboardStatsController@index')->name('horizon.stats.index');

    // Workload Routes...
    Route::get('/workload', '\Laravel\Horizon\Http\Controllers\WorkloadController@index')->name('horizon.workload.index');

    // Master Supervisor Routes...
    Route::get('/masters', '\Laravel\Horizon\Http\Controllers\MasterSupervisorController@index')->name('horizon.masters.index');

    // Monitoring Routes...
    Route::get('/monitoring', '\Laravel\Horizon\Http\Controllers\MonitoringController@index')->name('horizon.monitoring.index');
    Route::post('/monitoring', '\Laravel\Horizon\Http\Controllers\MonitoringController@store')->name('horizon.monitoring.store');
    Route::get('/monitoring/{tag}', '\Laravel\Horizon\Http\Controllers\MonitoringController@paginate')->name('horizon.monitoring-tag.paginate');
    Route::delete('/monitoring/{tag}', '\Laravel\Horizon\Http\Controllers\MonitoringController@destroy')->name('horizon.monitoring-tag.destroy');

    // Job Metric Routes...
    Route::get('/metrics/jobs', '\Laravel\Horizon\Http\Controllers\JobMetricsController@index')->name('horizon.jobs-metrics.index');
    Route::get('/metrics/jobs/{id}', '\Laravel\Horizon\Http\Controllers\JobMetricsController@show')->name('horizon.jobs-metrics.show');

    // Queue Metric Routes...
    Route::get('/metrics/queues', '\Laravel\Horizon\Http\Controllers\QueueMetricsController@index')->name('horizon.queues-metrics.index');
    Route::get('/metrics/queues/{id}', '\Laravel\Horizon\Http\Controllers\QueueMetricsController@show')->name('horizon.queues-metrics.show');

    // Job Routes...
    Route::get('/jobs/recent', '\Laravel\Horizon\Http\Controllers\RecentJobsController@index')->name('horizon.recent-jobs.index');
    Route::get('/jobs/failed', '\Laravel\Horizon\Http\Controllers\FailedJobsController@index')->name('horizon.failed-jobs.index');
    Route::get('/jobs/failed/{id}', '\Laravel\Horizon\Http\Controllers\FailedJobsController@show')->name('horizon.failed-jobs.show');
    Route::post('/jobs/retry/{id}', '\Laravel\Horizon\Http\Controllers\RetryController@store')->name('horizon.retry-jobs.show');
});

// Catch-all Route...
Route::get('/{view?}', '\Laravel\Horizon\Http\Controllers\HomeController@index')->where('view', '(.*)')->name('horizon.index');
