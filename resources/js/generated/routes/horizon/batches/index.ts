import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
import page1ac1a6 from './page'
/**
* @see \Laravel\Horizon\Http\Controllers\BatchPageController::page
* @see src/Http/Controllers/BatchPageController.php:28
* @route '/horizon/batches'
*/
export const page = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: page.url(options),
    method: 'get',
})

page.definition = {
    methods: ["get","head"],
    url: '/horizon/batches',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\BatchPageController::page
* @see src/Http/Controllers/BatchPageController.php:28
* @route '/horizon/batches'
*/
page.url = (options?: RouteQueryOptions) => {
    return page.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\BatchPageController::page
* @see src/Http/Controllers/BatchPageController.php:28
* @route '/horizon/batches'
*/
page.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: page.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\BatchPageController::page
* @see src/Http/Controllers/BatchPageController.php:28
* @route '/horizon/batches'
*/
page.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: page.url(options),
    method: 'head',
})

const batches = {
    page: Object.assign(page, page1ac1a6),
}

export default batches