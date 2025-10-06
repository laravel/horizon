<?php

namespace Laravel\Horizon\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;
use Inertia\Middleware;
use Laravel\Horizon\Horizon;

class HandleInertiaRequests extends Middleware
{
    /** {@inheritDoc} */
    protected $rootView = 'horizon::layout';

    /** {@inheritDoc} */
    #[\Override]
    public function version(Request $request)
    {
        return \sprintf('%s:%s', $this->rootView, parent::version($request));
    }

    /** {@inheritDoc} */
    #[\Override]
    public function share(Request $request)
    {
        return array_merge(parent::share($request), Horizon::scriptVariables());
    }

    /** {@inheritDoc} */
    #[\Override]
    public function handle(Request $request, Closure $next)
    {
        Config::set('inertia.ssr.enabled', false);

        if ($request->getScheme() === 'https') {
            Inertia::encryptHistory();
        }

        return parent::handle($request, $next);
    }
}
