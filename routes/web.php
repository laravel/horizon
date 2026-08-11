<?php

use Illuminate\Support\Facades\Route;

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
