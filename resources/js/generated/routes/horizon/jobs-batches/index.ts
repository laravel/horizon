import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::index
* @see src/Http/Controllers/BatchesController.php:40
* @route '/horizon/api/batches'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/horizon/api/batches',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::index
* @see src/Http/Controllers/BatchesController.php:40
* @route '/horizon/api/batches'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::index
* @see src/Http/Controllers/BatchesController.php:40
* @route '/horizon/api/batches'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::index
* @see src/Http/Controllers/BatchesController.php:40
* @route '/horizon/api/batches'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::show
* @see src/Http/Controllers/BatchesController.php:61
* @route '/horizon/api/batches/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/horizon/api/batches/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::show
* @see src/Http/Controllers/BatchesController.php:61
* @route '/horizon/api/batches/{id}'
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
* @see \Laravel\Horizon\Http\Controllers\BatchesController::show
* @see src/Http/Controllers/BatchesController.php:61
* @route '/horizon/api/batches/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::show
* @see src/Http/Controllers/BatchesController.php:61
* @route '/horizon/api/batches/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::retry
* @see src/Http/Controllers/BatchesController.php:108
* @route '/horizon/api/batches/retry/{id}'
*/
export const retry = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: retry.url(args, options),
    method: 'post',
})

retry.definition = {
    methods: ["post"],
    url: '/horizon/api/batches/retry/{id}',
} satisfies RouteDefinition<["post"]>

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::retry
* @see src/Http/Controllers/BatchesController.php:108
* @route '/horizon/api/batches/retry/{id}'
*/
retry.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return retry.definition.url
            .replace('{id}', encodeURIComponent(parsedArgs.id.toString()))
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::retry
* @see src/Http/Controllers/BatchesController.php:108
* @route '/horizon/api/batches/retry/{id}'
*/
retry.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: retry.url(args, options),
    method: 'post',
})

const jobsBatches = {
    index: Object.assign(index, index),
    show: Object.assign(show, show),
    retry: Object.assign(retry, retry),
}

export default jobsBatches