<?php

use Illuminate\Support\Facades\Route;
use Laravel\Horizon\Http\Controllers\BatchesController;
use Laravel\Horizon\Http\Controllers\CompletedJobsController;
use Laravel\Horizon\Http\Controllers\DashboardStatsController;
use Laravel\Horizon\Http\Controllers\FailedJobsController;
use Laravel\Horizon\Http\Controllers\HomeController;
use Laravel\Horizon\Http\Controllers\JobMetricsController;
use Laravel\Horizon\Http\Controllers\JobsController;
use Laravel\Horizon\Http\Controllers\MasterSupervisorController;
use Laravel\Horizon\Http\Controllers\MonitoringController;
use Laravel\Horizon\Http\Controllers\PendingJobsController;
use Laravel\Horizon\Http\Controllers\QueueMetricsController;
use Laravel\Horizon\Http\Controllers\RetryController;
use Laravel\Horizon\Http\Controllers\SilencedJobsController;
use Laravel\Horizon\Http\Controllers\WorkloadController;

Route::prefix('api')->group(function () {
    // Dashboard Routes...
    Route::get('/stats', [DashboardStatsController::class, 'index'])->name('horizon.stats.index');

    // Workload Routes...
    Route::get('/workload', [WorkloadController::class, 'index'])->name('horizon.workload.index');

    // Master Supervisor Routes...
    Route::get('/masters', [MasterSupervisorController::class, 'index'])->name('horizon.masters.index');

    // Monitoring Routes...
    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('horizon.monitoring.index');
    Route::post('/monitoring', [MonitoringController::class, 'store'])->name('horizon.monitoring.store');
    Route::get('/monitoring/{tag}', [MonitoringController::class, 'paginate'])->name('horizon.monitoring-tag.paginate');
    Route::delete('/monitoring/{tag}', [MonitoringController::class, 'destroy'])
        ->name('horizon.monitoring-tag.destroy')
        ->where('tag', '.*');

    // Job Metric Routes...
    Route::get('/metrics/jobs', [JobMetricsController::class, 'index'])->name('horizon.jobs-metrics.index');
    Route::get('/metrics/jobs/{id}', [JobMetricsController::class, 'show'])->name('horizon.jobs-metrics.show');

    // Queue Metric Routes...
    Route::get('/metrics/queues', [QueueMetricsController::class, 'index'])->name('horizon.queues-metrics.index');
    Route::get('/metrics/queues/{id}', [QueueMetricsController::class, 'show'])->name('horizon.queues-metrics.show');

    // Batches Routes...
    Route::get('/batches', [BatchesController::class, 'index'])->name('horizon.jobs-batches.index');
    Route::get('/batches/{id}', [BatchesController::class, 'show'])->name('horizon.jobs-batches.show');
    Route::post('/batches/retry/{id}', [BatchesController::class, 'retry'])->name('horizon.jobs-batches.retry');

    // Job Routes...
    Route::get('/jobs/pending', [PendingJobsController::class, 'index'])->name('horizon.pending-jobs.index');
    Route::get('/jobs/completed', [CompletedJobsController::class, 'index'])->name('horizon.completed-jobs.index');
    Route::get('/jobs/silenced', [SilencedJobsController::class, 'index'])->name('horizon.silenced-jobs.index');
    Route::get('/jobs/failed', [FailedJobsController::class, 'index'])->name('horizon.failed-jobs.index');
    Route::get('/jobs/failed/{id}', [FailedJobsController::class, 'show'])->name('horizon.failed-jobs.show');
    Route::post('/jobs/retry/{id}', [RetryController::class, 'store'])->name('horizon.retry-jobs.show');
    Route::get('/jobs/{id}', [JobsController::class, 'show'])->name('horizon.jobs.show');
});

// Catch-all Route...
Route::get('/{view?}', [HomeController::class, 'index'])->where('view', '(.*)')->name('horizon.index');
