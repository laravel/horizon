import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\JobPageController::page
* @see src/Http/Controllers/JobPageController.php:27
* @route '/horizon/jobs/pending'
*/
export const page = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: page.url(options),
    method: 'get',
})

page.definition = {
    methods: ["get","head"],
    url: '/horizon/jobs/pending',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\JobPageController::page
* @see src/Http/Controllers/JobPageController.php:27
* @route '/horizon/jobs/pending'
*/
page.url = (options?: RouteQueryOptions) => {
    return page.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\JobPageController::page
* @see src/Http/Controllers/JobPageController.php:27
* @route '/horizon/jobs/pending'
*/
page.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: page.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\JobPageController::page
* @see src/Http/Controllers/JobPageController.php:27
* @route '/horizon/jobs/pending'
*/
page.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: page.url(options),
    method: 'head',
})

/**
* @see \Laravel\Horizon\Http\Controllers\PendingJobsController::index
* @see src/Http/Controllers/PendingJobsController.php:36
* @route '/horizon/api/jobs/pending'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/horizon/api/jobs/pending',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\PendingJobsController::index
* @see src/Http/Controllers/PendingJobsController.php:36
* @route '/horizon/api/jobs/pending'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\PendingJobsController::index
* @see src/Http/Controllers/PendingJobsController.php:36
* @route '/horizon/api/jobs/pending'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\PendingJobsController::index
* @see src/Http/Controllers/PendingJobsController.php:36
* @route '/horizon/api/jobs/pending'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const pendingJobs = {
    page: Object.assign(page, page),
    index: Object.assign(index, index),
}

export default pendingJobs