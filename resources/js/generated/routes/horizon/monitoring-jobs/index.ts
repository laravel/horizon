import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringPageController::page
* @see src/Http/Controllers/MonitoringPageController.php:31
* @route '/horizon/monitoring/{tag}/jobs'
*/
export const page = (args: { tag: string | number } | [tag: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: page.url(args, options),
    method: 'get',
})

page.definition = {
    methods: ["get","head"],
    url: '/horizon/monitoring/{tag}/jobs',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringPageController::page
* @see src/Http/Controllers/MonitoringPageController.php:31
* @route '/horizon/monitoring/{tag}/jobs'
*/
page.url = (args: { tag: string | number } | [tag: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tag: args }
    }

    if (Array.isArray(args)) {
        args = {
            tag: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        tag: args.tag,
    }

    return page.definition.url
            .replace('{tag}', encodeURIComponent(parsedArgs.tag.toString()))
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringPageController::page
* @see src/Http/Controllers/MonitoringPageController.php:31
* @route '/horizon/monitoring/{tag}/jobs'
*/
page.get = (args: { tag: string | number } | [tag: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: page.url(args, options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringPageController::page
* @see src/Http/Controllers/MonitoringPageController.php:31
* @route '/horizon/monitoring/{tag}/jobs'
*/
page.head = (args: { tag: string | number } | [tag: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: page.url(args, options),
    method: 'head',
})

const monitoringJobs = {
    page: Object.assign(page, page),
}

export default monitoringJobs