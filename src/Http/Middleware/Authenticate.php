<?php

namespace Laravel\Horizon\Http\Middleware;

use Laravel\Horizon\Horizon;

class Authenticate
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response|null
     */
    public function handle($request, $next)
    {
        if (! Horizon::check($request)) {
            abort($this->statusCode());
        }

        return $next($request);
    }

    /**
     * Determine the status code returned for unauthorized requests.
     *
     * @return int
     */
    private function statusCode()
    {
        $code = config('horizon.unauthorized_status');

        if (! in_array($code, [403, 404])) {
            return 403;
        }

        return $code;
    }
}
