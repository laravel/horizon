<?php

namespace Laravel\Horizon\Http\Controllers;

use Laravel\Horizon\Dashboard\Workload;

class WorkloadController extends Controller
{
    public function __construct(private Workload $workload)
    {
        parent::__construct();
    }

    /**
     * Get the current queue workload for the application.
     *
     * @return array
     */
    public function index()
    {
        return $this->workload->get();
    }
}
