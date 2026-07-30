import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\QueueMetricsController::index
* @see src/Http/Controllers/QueueMetricsController.php:34
* @route '/horizon/api/metrics/queues'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/horizon/api/metrics/queues',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\QueueMetricsController::index
* @see src/Http/Controllers/QueueMetricsController.php:34
* @route '/horizon/api/metrics/queues'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\QueueMetricsController::index
* @see src/Http/Controllers/QueueMetricsController.php:34
* @route '/horizon/api/metrics/queues'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\QueueMetricsController::index
* @see src/Http/Controllers/QueueMetricsController.php:34
* @route '/horizon/api/metrics/queues'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \Laravel\Horizon\Http\Controllers\QueueMetricsController::show
* @see src/Http/Controllers/QueueMetricsController.php:45
* @route '/horizon/api/metrics/queues/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/horizon/api/metrics/queues/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\QueueMetricsController::show
* @see src/Http/Controllers/QueueMetricsController.php:45
* @route '/horizon/api/metrics/queues/{id}'
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
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\QueueMetricsController::show
* @see src/Http/Controllers/QueueMetricsController.php:45
* @route '/horizon/api/metrics/queues/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\QueueMetricsController::show
* @see src/Http/Controllers/QueueMetricsController.php:45
* @route '/horizon/api/metrics/queues/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

const queuesMetrics = {
    index: Object.assign(index, index),
    show: Object.assign(show, show),
}

export default queuesMetrics