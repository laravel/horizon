import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
import pendingJobs from './pending-jobs'
import completedJobs from './completed-jobs'
import failedJobs from './failed-jobs'
import silencedJobs from './silenced-jobs'
import jobs from './jobs'
import monitoring from './monitoring'
import monitoringJobs from './monitoring-jobs'
import monitoringFailed from './monitoring-failed'
import metrics from './metrics'
import batches from './batches'
import stats from './stats'
import workload from './workload'
import queues from './queues'
import masters from './masters'
import monitoringTag from './monitoring-tag'
import jobsMetrics from './jobs-metrics'
import queuesMetrics from './queues-metrics'
import jobsBatches from './jobs-batches'
import retryJobs from './retry-jobs'
/**
* @see \Laravel\Horizon\Http\Controllers\DashboardController::index
* @see src/Http/Controllers/DashboardController.php:23
* @route '/horizon'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/horizon',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\DashboardController::index
* @see src/Http/Controllers/DashboardController.php:23
* @route '/horizon'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\DashboardController::index
* @see src/Http/Controllers/DashboardController.php:23
* @route '/horizon'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\DashboardController::index
* @see src/Http/Controllers/DashboardController.php:23
* @route '/horizon'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \Laravel\Horizon\Http\Controllers\DashboardController::dashboard
* @see src/Http/Controllers/DashboardController.php:23
* @route '/horizon/dashboard'
*/
export const dashboard = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

dashboard.definition = {
    methods: ["get","head"],
    url: '/horizon/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\DashboardController::dashboard
* @see src/Http/Controllers/DashboardController.php:23
* @route '/horizon/dashboard'
*/
dashboard.url = (options?: RouteQueryOptions) => {
    return dashboard.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\DashboardController::dashboard
* @see src/Http/Controllers/DashboardController.php:23
* @route '/horizon/dashboard'
*/
dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\DashboardController::dashboard
* @see src/Http/Controllers/DashboardController.php:23
* @route '/horizon/dashboard'
*/
dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard.url(options),
    method: 'head',
})

const horizon = {
    index: Object.assign(index, index),
    dashboard: Object.assign(dashboard, dashboard),
    pendingJobs: Object.assign(pendingJobs, pendingJobs),
    completedJobs: Object.assign(completedJobs, completedJobs),
    failedJobs: Object.assign(failedJobs, failedJobs),
    silencedJobs: Object.assign(silencedJobs, silencedJobs),
    jobs: Object.assign(jobs, jobs),
    monitoring: Object.assign(monitoring, monitoring),
    monitoringJobs: Object.assign(monitoringJobs, monitoringJobs),
    monitoringFailed: Object.assign(monitoringFailed, monitoringFailed),
    metrics: Object.assign(metrics, metrics),
    batches: Object.assign(batches, batches),
    stats: Object.assign(stats, stats),
    workload: Object.assign(workload, workload),
    queues: Object.assign(queues, queues),
    masters: Object.assign(masters, masters),
    monitoringTag: Object.assign(monitoringTag, monitoringTag),
    jobsMetrics: Object.assign(jobsMetrics, jobsMetrics),
    queuesMetrics: Object.assign(queuesMetrics, queuesMetrics),
    jobsBatches: Object.assign(jobsBatches, jobsBatches),
    retryJobs: Object.assign(retryJobs, retryJobs),
}

export default horizon