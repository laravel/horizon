<?php

declare(strict_types=1);

namespace Laravel\Horizon\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Laravel\Horizon\Contracts\MetricsRepository;

final class MetricsPageController extends Controller
{
    public function __construct(private readonly MetricsRepository $metrics)
    {
        parent::__construct();
    }

    public function index(string $type): Response
    {
        return Inertia::render('metrics', [
            'type' => $type,
            'metrics' => fn (): array => $type === 'jobs'
                ? $this->metrics->measuredJobs()
                : $this->metrics->measuredQueues(),
        ]);
    }
}
