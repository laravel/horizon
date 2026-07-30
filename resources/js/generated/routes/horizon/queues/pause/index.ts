import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\QueuePauseController::store
* @see src/Http/Controllers/QueuePauseController.php:23
* @route '/horizon/api/queues/{connection}/{queue}/pause'
*/
export const store = (args: { connection: string | number, queue: string | number } | [connection: string | number, queue: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/horizon/api/queues/{connection}/{queue}/pause',
} satisfies RouteDefinition<["post"]>

/**
* @see \Laravel\Horizon\Http\Controllers\QueuePauseController::store
* @see src/Http/Controllers/QueuePauseController.php:23
* @route '/horizon/api/queues/{connection}/{queue}/pause'
*/
store.url = (args: { connection: string | number, queue: string | number } | [connection: string | number, queue: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            connection: args[0],
            queue: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        connection: args.connection,
        queue: args.queue,
    }

    return store.definition.url
            .replace('{connection}', parsedArgs.connection.toString())
            .replace('{queue}', parsedArgs.queue.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\QueuePauseController::store
* @see src/Http/Controllers/QueuePauseController.php:23
* @route '/horizon/api/queues/{connection}/{queue}/pause'
*/
store.post = (args: { connection: string | number, queue: string | number } | [connection: string | number, queue: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \Laravel\Horizon\Http\Controllers\QueuePauseController::destroy
* @see src/Http/Controllers/QueuePauseController.php:49
* @route '/horizon/api/queues/{connection}/{queue}/pause'
*/
export const destroy = (args: { connection: string | number, queue: string | number } | [connection: string | number, queue: string | number ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/horizon/api/queues/{connection}/{queue}/pause',
} satisfies RouteDefinition<["delete"]>

/**
* @see \Laravel\Horizon\Http\Controllers\QueuePauseController::destroy
* @see src/Http/Controllers/QueuePauseController.php:49
* @route '/horizon/api/queues/{connection}/{queue}/pause'
*/
destroy.url = (args: { connection: string | number, queue: string | number } | [connection: string | number, queue: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            connection: args[0],
            queue: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        connection: args.connection,
        queue: args.queue,
    }

    return destroy.definition.url
            .replace('{connection}', parsedArgs.connection.toString())
            .replace('{queue}', parsedArgs.queue.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\QueuePauseController::destroy
* @see src/Http/Controllers/QueuePauseController.php:49
* @route '/horizon/api/queues/{connection}/{queue}/pause'
*/
destroy.delete = (args: { connection: string | number, queue: string | number } | [connection: string | number, queue: string | number ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

const pause = {
    store: Object.assign(store, store),
    destroy: Object.assign(destroy, destroy),
}

export default pause