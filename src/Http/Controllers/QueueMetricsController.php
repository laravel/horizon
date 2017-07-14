<?php

namespace Laravel\Horizon\Http\Controllers;

use Laravel\Horizon\Contracts\MetricsRepository;

class QueueMetricsController extends Controller
{
    /**
     * The metrics repository implementation.
     *
     * @var MetricsRepository
     */
    public $metrics;

    /**
     * Create a new controller instance.
     *
     * @param  MetricsRepository  $jobs
     * @return void
     */
    public function __construct(MetricsRepository $metrics)
    {
        parent::__construct();

        $this->metrics = $metrics;
    }

    /**
     * Get all of the measured queues.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return $this->metrics->measuredQueues();
    }

    /**
     * Get metrics for a given queue.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        return collect($this->metrics->snapshotsForQueue($slug))->map(function ($record) {
            $record->runtime = ceil($record->runtime / 1000);
            $record->throughput = (int) $record->throughput;

            return $record;
        });
    }
}
