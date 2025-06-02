<?php

if (!function_exists('horizon_stats')) {
    function horizon_stats()
    {
        $dashboard = new \Laravel\Horizon\Http\Controllers\DashboardStatsController();
        return $dashboard->index();
    }
}
