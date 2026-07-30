<?php

namespace Laravel\Horizon\Http\Controllers;

use Laravel\Horizon\Dashboard\DashboardStats;
use Laravel\Horizon\Support\NavigationCounts;

class DashboardStatsController extends Controller
{
    public function __construct(
        private DashboardStats $stats,
        private NavigationCounts $navigationCounts,
    ) {
        parent::__construct();
    }

    /**
     * Get the key performance stats for the dashboard.
     *
     * @return array
     */
    public function index()
    {
        return [
            ...$this->stats->legacy(),
            'navigationCounts' => $this->navigationCounts->get(),
        ];
    }
}
