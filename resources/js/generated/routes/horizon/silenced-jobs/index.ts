import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\JobPageController::page
* @see src/Http/Controllers/JobPageController.php:73
* @route '/horizon/jobs/silenced'
*/
export const page = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: page.url(options),
    method: 'get',
})

page.definition = {
    methods: ["get","head"],
    url: '/horizon/jobs/silenced',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\JobPageController::page
* @see src/Http/Controllers/JobPageController.php:73
* @route '/horizon/jobs/silenced'
*/
page.url = (options?: RouteQueryOptions) => {
    return page.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\JobPageController::page
* @see src/Http/Controllers/JobPageController.php:73
* @route '/horizon/jobs/silenced'
*/
page.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: page.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\JobPageController::page
* @see src/Http/Controllers/JobPageController.php:73
* @route '/horizon/jobs/silenced'
*/
page.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: page.url(options),
    method: 'head',
})

/**
* @see \Laravel\Horizon\Http\Controllers\SilencedJobsController::index
* @see src/Http/Controllers/SilencedJobsController.php:36
* @route '/horizon/api/jobs/silenced'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/horizon/api/jobs/silenced',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\SilencedJobsController::index
* @see src/Http/Controllers/SilencedJobsController.php:36
* @route '/horizon/api/jobs/silenced'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\SilencedJobsController::index
* @see src/Http/Controllers/SilencedJobsController.php:36
* @route '/horizon/api/jobs/silenced'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\SilencedJobsController::index
* @see src/Http/Controllers/SilencedJobsController.php:36
* @route '/horizon/api/jobs/silenced'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const silencedJobs = {
    page: Object.assign(page, page),
    index: Object.assign(index, index),
}

export default silencedJobs