import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\RetryController::show
* @see src/Http/Controllers/RetryController.php:15
* @route '/horizon/api/jobs/retry/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: show.url(args, options),
    method: 'post',
})

show.definition = {
    methods: ["post"],
    url: '/horizon/api/jobs/retry/{id}',
} satisfies RouteDefinition<["post"]>

/**
* @see \Laravel\Horizon\Http\Controllers\RetryController::show
* @see src/Http/Controllers/RetryController.php:15
* @route '/horizon/api/jobs/retry/{id}'
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
* @see \Laravel\Horizon\Http\Controllers\RetryController::show
* @see src/Http/Controllers/RetryController.php:15
* @route '/horizon/api/jobs/retry/{id}'
*/
show.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: show.url(args, options),
    method: 'post',
})

const retryJobs = {
    show: Object.assign(show, show),
}

export default retryJobs