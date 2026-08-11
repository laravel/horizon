<?php

declare(strict_types=1);

namespace Laravel\Horizon\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Laravel\Horizon\Dashboard\DashboardStats;
use Laravel\Horizon\Dashboard\MasterSupervisors;
use Laravel\Horizon\Dashboard\Workload;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardStats $stats,
        private readonly Workload $workload,
        private readonly MasterSupervisors $masters,
    ) {
        parent::__construct();
    }

    public function index(): Response
    {
        return Inertia::render('dashboard', [
            'stats' => fn (): array => $this->stats->get(),
            'workload' => fn (): array => $this->workload->get(),
            'masters' => fn (): array => $this->masters->get()->values()->all(),
        ]);
    }
}
