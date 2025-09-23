<?php

namespace Laravel\Horizon\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;
use Inertia\Middleware;
use Inertia\ResponseFactory;

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
        return array_merge(parent::share($request), [
            'isDownForMaintenance' => App::isDownForMaintenance(),
        ]);
    }

    /** {@inheritDoc} */
    #[\Override]
    public function handle(Request $request, Closure $next)
    {
        Config::set('inertia.ssr.enabled', false);

        if (method_exists(ResponseFactory::class, 'encryptHistory') && $request->getScheme() === 'https') {
            Inertia::encryptHistory(); // @phpstan-ignore staticMethod.notFound
        }

        return parent::handle($request, $next);
    }
}
