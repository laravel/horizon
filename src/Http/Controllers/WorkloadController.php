<?php

namespace Laravel\Horizon\Http\Controllers;

use Laravel\Horizon\Contracts\WorkloadRepository;

class WorkloadController extends Controller
{
    /**
     * Get the current queue workload for the application.
     *
     * @param  WorkloadRepository  $workload
     * @return \Illuminate\Http\Response
     */
    public function index(WorkloadRepository $workload)
    {
        return collect($workload->get())->sortBy('name')->values()->toArray();
    }
}
