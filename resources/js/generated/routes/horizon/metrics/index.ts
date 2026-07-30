import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
import page1ac1a6 from './page'
/**
* @see \Laravel\Horizon\Http\Controllers\MetricsPageController::page
* @see src/Http/Controllers/MetricsPageController.php:18
* @route '/horizon/metrics/{type}'
*/
export const page = (args: { type: string | number } | [type: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: page.url(args, options),
    method: 'get',
})

page.definition = {
    methods: ["get","head"],
    url: '/horizon/metrics/{type}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\MetricsPageController::page
* @see src/Http/Controllers/MetricsPageController.php:18
* @route '/horizon/metrics/{type}'
*/
page.url = (args: { type: string | number } | [type: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { type: args }
    }

    if (Array.isArray(args)) {
        args = {
            type: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        type: args.type,
    }

    return page.definition.url
            .replace('{type}', encodeURIComponent(parsedArgs.type.toString()))
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\MetricsPageController::page
* @see src/Http/Controllers/MetricsPageController.php:18
* @route '/horizon/metrics/{type}'
*/
page.get = (args: { type: string | number } | [type: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: page.url(args, options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\MetricsPageController::page
* @see src/Http/Controllers/MetricsPageController.php:18
* @route '/horizon/metrics/{type}'
*/
page.head = (args: { type: string | number } | [type: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: page.url(args, options),
    method: 'head',
})

const metrics = {
    page: Object.assign(page, page1ac1a6),
}

export default metrics