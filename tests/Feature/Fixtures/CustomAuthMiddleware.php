<?php

namespace Laravel\Horizon\Tests\Feature\Fixtures;

class CustomAuthMiddleware
{
    public function handle($request, $next)
    {
        if (! $request->input('passes')) {
            abort(403);
        }

        return $next($request);
    }
}
