<?php

namespace Laravel\Horizon\Http\Controllers;

use Laravel\Horizon\Contracts\MetricsRepository;

class JobMetricsController extends Controller
{
    /**
     * The metrics repository implementation.
     *
     * @var \Laravel\Horizon\Contracts\MetricsRepository
     */
    public $metrics;

    /**
     * Create a new controller instance.
     *
     * @param  \Laravel\Horizon\Contracts\MetricsRepository  $metrics
     * @return void
     */
    public function __construct(MetricsRepository $metrics)
    {
        parent::__construct();

        $this->metrics = $metrics;
    }

    /**
     * Get all of the measured jobs.
     *
     * @return array
     */
    public function index()
    {
        return $this->metrics->measuredJobs();
    }

    /**
     * Get metrics for a given job.
     *
     * @param  string  $id
     * @return \Illuminate\Support\Collection
     */
    public function show($id)
    {
        return collect($this->metrics->snapshotsForJob($id))
            ->map(function ($record) {
                $record->runtime = round($record->runtime / 1000, 3);
                $record->throughput = (int) $record->throughput;

                return $record;
            });
    }
}
