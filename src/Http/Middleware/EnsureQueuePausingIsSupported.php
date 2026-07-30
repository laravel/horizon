<?php

declare(strict_types=1);

namespace Laravel\Horizon\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Horizon\Support\FrameworkCapabilities;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureQueuePausingIsSupported
{
    public function __construct(private FrameworkCapabilities $capabilities)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->capabilities->queuePausing) {
            abort(404);
        }

        return $next($request);
    }
}
