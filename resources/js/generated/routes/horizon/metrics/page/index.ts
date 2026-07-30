import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\MetricController::show
* @see src/Http/Controllers/MetricController.php:19
* @route '/horizon/metrics/{type}/{name}'
*/
export const show = (args: { type: string | number, name: string | number } | [type: string | number, name: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/horizon/metrics/{type}/{name}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\MetricController::show
* @see src/Http/Controllers/MetricController.php:19
* @route '/horizon/metrics/{type}/{name}'
*/
show.url = (args: { type: string | number, name: string | number } | [type: string | number, name: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            type: args[0],
            name: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        type: args.type,
        name: args.name,
    }

    return show.definition.url
            .replace('{type}', encodeURIComponent(parsedArgs.type.toString()))
            .replace('{name}', encodeURIComponent(parsedArgs.name.toString()))
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\MetricController::show
* @see src/Http/Controllers/MetricController.php:19
* @route '/horizon/metrics/{type}/{name}'
*/
show.get = (args: { type: string | number, name: string | number } | [type: string | number, name: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\MetricController::show
* @see src/Http/Controllers/MetricController.php:19
* @route '/horizon/metrics/{type}/{name}'
*/
show.head = (args: { type: string | number, name: string | number } | [type: string | number, name: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

const page = {
    show: Object.assign(show, show),
}

export default page