<?php

declare(strict_types=1);

namespace Laravel\Horizon\Http\Middleware;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Laravel\Horizon\Assets\AssetManifest;
use Laravel\Horizon\Dashboard\HorizonStatus;
use Laravel\Horizon\Dashboard\PendingJobCounts;
use Laravel\Horizon\Support\FrameworkCapabilities;
use Laravel\Horizon\Support\NavigationCounts;

final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'horizon::app';

    /** @var array<int, string> */
    protected $withoutSsr = [];

    public function __construct(
        private readonly Application $application,
        private readonly HorizonStatus $status,
        private readonly PendingJobCounts $pendingJobs,
        private readonly NavigationCounts $navigationCounts,
        private readonly FrameworkCapabilities $capabilities,
        private readonly AssetManifest $assets,
    ) {
        $this->withoutSsr = $this->ssrExcludedPaths();
    }

    public function version(Request $request): string
    {
        return $this->assets->version();
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'horizon' => [
                'baseUrl' => $this->baseUrl(),
                'name' => config('horizon.name'),
                'pollInterval' => 5000,
                'maintenanceMode' => $this->application->isDownForMaintenance(),
                'status' => fn (): string => $this->status->current(),
                'processing' => fn (): bool => $this->pendingJobs->processing(),
                'capabilities' => $this->capabilities->toArray(),
            ],
            'navigationCounts' => fn (): array => $this->navigationCounts->get(),
        ];
    }

    private function baseUrl(): string
    {
        $segments = array_filter([
            trim((string) config('horizon.proxy_path', ''), '/'),
            trim((string) config('horizon.path', ''), '/'),
        ]);

        return '/'.implode('/', $segments);
    }

    /**
     * Additive SSR path exclusions for Horizon UI only (never overwrites consumer disableSsr).
     *
     * @return array<int, string>
     */
    private function ssrExcludedPaths(): array
    {
        $path = trim((string) config('horizon.path', ''), '/');
        $proxy = trim((string) config('horizon.proxy_path', ''), '/');

        if ($path !== '') {
            $prefixes = [$path];

            if ($proxy !== '') {
                $prefixes[] = $proxy.'/'.$path;
            }

            $patterns = [];

            foreach (array_values(array_unique($prefixes)) as $prefix) {
                $patterns[] = $prefix;
                $patterns[] = $prefix.'/*';
            }

            return $patterns;
        }

        return $this->rootMountedSsrExcludedPaths($proxy);
    }

    /**
     * Root-mounted Horizon UI routes only. Include unprefixed patterns and, when set,
     * proxy-prefixed equivalents (proxies may strip the prefix before Laravel).
     *
     * @return array<int, string>
     */
    private function rootMountedSsrExcludedPaths(string $proxy = ''): array
    {
        $segments = [
            '',
            'dashboard',
            'jobs/*',
            'monitoring',
            'monitoring/*',
            'metrics',
            'metrics/*',
            'batches',
            'batches/*',
            'failed',
            'failed/*',
        ];

        $patterns = [];

        foreach ($segments as $segment) {
            $patterns[] = $segment === '' ? '/' : $segment;

            if ($proxy !== '') {
                $patterns[] = $segment === '' ? $proxy : $proxy.'/'.$segment;
            }
        }

        return $patterns;
    }
}
