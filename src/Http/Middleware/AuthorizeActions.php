<?php

namespace Laravel\Horizon\Http\Middleware;

use Laravel\Horizon\Events\DashboardActionPerformed;
use Laravel\Horizon\Exceptions\ForbiddenException;
use Laravel\Horizon\Horizon;

class AuthorizeActions
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response
     */
    public function handle($request, $next)
    {
        if (! Horizon::canManage($request)) {
            throw ForbiddenException::make();
        }

        return tap($next($request), function ($response) use ($request) {
            if ($response->getStatusCode() < 400) {
                event(new DashboardActionPerformed(
                    $request->user(),
                    $request->route()?->getName(),
                    $request->route()?->parameters() ?? [],
                    $request->all(),
                ));
            }
        });
    }
}
