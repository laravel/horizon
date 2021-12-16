<?php

$router->group([
    'prefix' => 'api'
], function ($router) {
    // Dashboard Routes...
    $router->get('/stats', [
        'as' => 'horizon.stats.index',
        'uses' => 'DashboardStatsController@index'
    ]);

    // Workload Routes...
    $router->get('/workload', [
        'as' => 'horizon.workload.index',
        'uses' => 'WorkloadController@index'
    ]);

    // Master Supervisor Routes...
    $router->get('/masters', [
        'as' => 'horizon.masters.index',
        'uses' => 'MasterSupervisorController@index'
    ]);

    // Monitoring Routes...
    $router->get('/monitoring', [
        'as' => 'horizon.monitoring.index',
        'uses' => 'MonitoringController@index'
    ]);
    $router->post('/monitoring', [
        'as' => 'horizon.monitoring.store',
        'uses' => 'MonitoringController@store'
    ]);
    $router->get('/monitoring/{tag}', [
        'as' => 'horizon.monitoring-tag.paginate',
        'uses' => 'MonitoringController@paginate'
    ]);
    $router->delete('/monitoring/{tag}', [
        'as' => 'horizon.monitoring-tag.destroy',
        'uses' => 'MonitoringController@destroy'
    ]);

    // Job Metric Routes...
    $router->get('/metrics/jobs', [
        'as' => 'horizon.jobs-metrics.index',
        'uses' => 'JobMetricsController@index'
    ]);
    $router->get('/metrics/jobs/{id}', [
        'as' => 'horizon.jobs-metrics.show',
        'uses' => 'JobMetricsController@show'
    ]);

    // Queue Metric Routes...
    $router->get('/metrics/queues', [
        'as' => 'horizon.queues-metrics.index',
        'uses' => 'QueueMetricsController@index'
    ]);
    $router->get('/metrics/queues/{id}', [
        'as' => 'horizon.queues-metrics.show',
        'uses' => 'QueueMetricsController@show'
    ]);

    // Batches Routes...
    $router->get('/batches', [
        'as' => 'horizon.jobs-batches.index',
        'uses' => 'BatchesController@index'
    ]);
    $router->get('/batches/{id}', [
        'as' => 'horizon.jobs-batches.show',
        'uses' => 'BatchesController@show'
    ]);
    $router->post('/batches/retry/{id}', [
        'as' => 'horizon.jobs-batches.retry',
        'uses' => 'BatchesController@retry'
    ]);

    // Job Routes...
    $router->get('/jobs/pending', [
        'as' => 'horizon.pending-jobs.index',
        'uses' => 'PendingJobsController@index'
    ]);
    $router->get('/jobs/completed', [
        'as' => 'horizon.completed-jobs.index',
        'uses' => 'CompletedJobsController@index'
    ]);
    $router->get('/jobs/failed', [
        'as' => 'horizon.failed-jobs.index',
        'uses' => 'FailedJobsController@index'
    ]);
    $router->get('/jobs/failed/{id}', [
        'as' => 'horizon.failed-jobs.show',
        'uses' => 'FailedJobsController@show'
    ]);
    $router->post('/jobs/retry/{id}', [
        'as' => 'horizon.retry-jobs.show',
        'uses' => 'RetryController@store'
    ]);
    $router->get('/jobs/{id}', [
        'as' => 'horizon.jobs.show',
        'uses' => 'JobsController@show'
    ]);
});

// Catch-all Route...
$router->get('/{view?}', [
    'as' => 'horizon.index',
    'uses' => 'HomeController@index'
]);
