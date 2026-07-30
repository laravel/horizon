import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\WorkloadController::index
* @see src/Http/Controllers/WorkloadController.php:19
* @route '/horizon/api/workload'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/horizon/api/workload',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\WorkloadController::index
* @see src/Http/Controllers/WorkloadController.php:19
* @route '/horizon/api/workload'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\WorkloadController::index
* @see src/Http/Controllers/WorkloadController.php:19
* @route '/horizon/api/workload'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\WorkloadController::index
* @see src/Http/Controllers/WorkloadController.php:19
* @route '/horizon/api/workload'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const workload = {
    index: Object.assign(index, index),
}

export default workload