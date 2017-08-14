<?php

use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    // Dashboard Routes...
    Route::get('/stats', 'DashboardStatsController@index');

    // Workload Routes...
    Route::get('/workload', 'WorkloadController@index');

    // Master Supervisor Routes...
    Route::get('/masters', 'MasterSupervisorController@index');

    // Monitoring Routes...
    Route::get('/monitoring', 'MonitoringController@index');
    Route::post('/monitoring', 'MonitoringController@store');
    Route::get('/monitoring/{tag}', 'MonitoringController@paginate');
    Route::delete('/monitoring/{tag}', 'MonitoringController@destroy');

    // Job Metric Routes...
    Route::get('/metrics/jobs', 'JobMetricsController@index');
    Route::get('/metrics/jobs/{id}', 'JobMetricsController@show');

    // Queue Metric Routes...
    Route::get('/metrics/queues', 'QueueMetricsController@index');
    Route::get('/metrics/queues/{id}', 'QueueMetricsController@show');

    // Job Routes...
    Route::get('/jobs/recent', 'RecentJobsController@index');
    Route::get('/jobs/failed', 'FailedJobsController@index');
    Route::get('/jobs/failed/{id}', 'FailedJobsController@show');
    Route::post('/jobs/retry/{id}', 'RetryController@store');
});

// Catch-all Route...
Route::get('/{view?}', 'HomeController@index')->where('view', '(.*)');
