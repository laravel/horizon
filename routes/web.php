<?php

use Illuminate\Support\Facades\Route;

// Dashboard Routes...
Route::get('/api/stats', 'DashboardStatsController@index');

// Workload Routes...
Route::get('/api/workload', 'WorkloadController@index');

// Master Supervisor Routes...
Route::get('/api/masters', 'MasterSupervisorController@index');

// Monitoring Routes...
Route::get('/api/monitoring', 'MonitoringController@index');
Route::post('/api/monitoring', 'MonitoringController@store');
Route::get('/api/monitoring/{tag}', 'MonitoringController@paginate');
Route::delete('/api/monitoring/{tag}', 'MonitoringController@destroy');

// Job Metric Routes...
Route::get('/api/metrics/jobs', 'JobMetricsController@index');
Route::get('/api/metrics/jobs/{id}', 'JobMetricsController@show');

// Queue Metric Routes...
Route::get('/api/metrics/queues', 'QueueMetricsController@index');
Route::get('/api/metrics/queues/{id}', 'QueueMetricsController@show');

// Job Routes...
Route::get('/api/jobs/recent', 'RecentJobsController@index');
Route::get('/api/jobs/failed', 'FailedJobsController@index');
Route::get('/api/jobs/failed/{id}', 'FailedJobsController@show');
Route::post('/api/jobs/retry/{id}', 'RetryController@store');

// Catch-all Routes...
Route::get('/', 'HomeController@index');
Route::get('{view}', 'HomeController@index')->where('view', '(.*)');
