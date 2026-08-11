import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
import page from './page'
/**
* @see \Laravel\Horizon\Http\Controllers\JobsController::show
* @see src/Http/Controllers/JobsController.php:35
* @route '/horizon/api/jobs/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/horizon/api/jobs/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\JobsController::show
* @see src/Http/Controllers/JobsController.php:35
* @route '/horizon/api/jobs/{id}'
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
* @see \Laravel\Horizon\Http\Controllers\JobsController::show
* @see src/Http/Controllers/JobsController.php:35
* @route '/horizon/api/jobs/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\JobsController::show
* @see src/Http/Controllers/JobsController.php:35
* @route '/horizon/api/jobs/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

const jobs = {
    page: Object.assign(page, page),
    show: Object.assign(show, show),
}

export default jobs