import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\JobMetricsController::index
* @see src/Http/Controllers/JobMetricsController.php:34
* @route '/horizon/api/metrics/jobs'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/horizon/api/metrics/jobs',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\JobMetricsController::index
* @see src/Http/Controllers/JobMetricsController.php:34
* @route '/horizon/api/metrics/jobs'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\JobMetricsController::index
* @see src/Http/Controllers/JobMetricsController.php:34
* @route '/horizon/api/metrics/jobs'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\JobMetricsController::index
* @see src/Http/Controllers/JobMetricsController.php:34
* @route '/horizon/api/metrics/jobs'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \Laravel\Horizon\Http\Controllers\JobMetricsController::show
* @see src/Http/Controllers/JobMetricsController.php:45
* @route '/horizon/api/metrics/jobs/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/horizon/api/metrics/jobs/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\JobMetricsController::show
* @see src/Http/Controllers/JobMetricsController.php:45
* @route '/horizon/api/metrics/jobs/{id}'
*/
show.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return show.definition.url
            .replace('{id}', encodeURIComponent(parsedArgs.id.toString()))
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\JobMetricsController::show
* @see src/Http/Controllers/JobMetricsController.php:45
* @route '/horizon/api/metrics/jobs/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\JobMetricsController::show
* @see src/Http/Controllers/JobMetricsController.php:45
* @route '/horizon/api/metrics/jobs/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

const jobsMetrics = {
    index: Object.assign(index, index),
    show: Object.assign(show, show),
}

export default jobsMetrics