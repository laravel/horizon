<?php

declare(strict_types=1);

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Laravel\Horizon\Support\HorizonUrl;

/**
 * Temporary redirects from upstream Horizon 5.x Vue-router paths to Inertia pages.
 */
class LegacyRedirectController extends Controller
{
    /**
     * GET /metrics → jobs metrics page.
     */
    public function metrics(): RedirectResponse
    {
        return $this->temporary(HorizonUrl::route('horizon.metrics.page', ['type' => 'jobs']));
    }

    /**
     * GET /monitoring/{tag} → monitored-tag jobs page.
     */
    public function monitoringTag(string $tag): RedirectResponse
    {
        return $this->temporary(HorizonUrl::route('horizon.monitoring-jobs.page', ['tag' => $tag]));
    }

    /**
     * GET /failed → failed jobs page.
     */
    public function failedJobs(): RedirectResponse
    {
        return $this->temporary(HorizonUrl::route('horizon.failed-jobs.page'));
    }

    /**
     * GET /failed/{id} → failed job detail page.
     */
    public function failedJob(string $id): RedirectResponse
    {
        return $this->temporary(HorizonUrl::route('horizon.jobs.page.show', [
            'type' => 'failed',
            'id' => $id,
        ]));
    }

    private function temporary(string $path): RedirectResponse
    {
        if ($query = request()->getQueryString()) {
            $path .= (str_contains($path, '?') ? '&' : '?').$query;
        }

        return redirect()->to($path, 302);
    }
}
