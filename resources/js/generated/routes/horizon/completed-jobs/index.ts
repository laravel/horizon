import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\JobPageController::page
* @see src/Http/Controllers/JobPageController.php:37
* @route '/horizon/jobs/completed'
*/
export const page = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: page.url(options),
    method: 'get',
})

page.definition = {
    methods: ["get","head"],
    url: '/horizon/jobs/completed',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\JobPageController::page
* @see src/Http/Controllers/JobPageController.php:37
* @route '/horizon/jobs/completed'
*/
page.url = (options?: RouteQueryOptions) => {
    return page.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\JobPageController::page
* @see src/Http/Controllers/JobPageController.php:37
* @route '/horizon/jobs/completed'
*/
page.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: page.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\JobPageController::page
* @see src/Http/Controllers/JobPageController.php:37
* @route '/horizon/jobs/completed'
*/
page.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: page.url(options),
    method: 'head',
})

/**
* @see \Laravel\Horizon\Http\Controllers\CompletedJobsController::index
* @see src/Http/Controllers/CompletedJobsController.php:36
* @route '/horizon/api/jobs/completed'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/horizon/api/jobs/completed',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\CompletedJobsController::index
* @see src/Http/Controllers/CompletedJobsController.php:36
* @route '/horizon/api/jobs/completed'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\CompletedJobsController::index
* @see src/Http/Controllers/CompletedJobsController.php:36
* @route '/horizon/api/jobs/completed'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\CompletedJobsController::index
* @see src/Http/Controllers/CompletedJobsController.php:36
* @route '/horizon/api/jobs/completed'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const completedJobs = {
    page: Object.assign(page, page),
    index: Object.assign(index, index),
}

export default completedJobs