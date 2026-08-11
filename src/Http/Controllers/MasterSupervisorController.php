<?php

namespace Laravel\Horizon\Http\Controllers;

use Laravel\Horizon\Dashboard\MasterSupervisors;

class MasterSupervisorController extends Controller
{
    public function __construct(private MasterSupervisors $masters)
    {
        parent::__construct();
    }

    /**
     * Get all of the master supervisors and their underlying supervisors.
     *
     * @return \Illuminate\Support\Collection
     */
    public function index()
    {
        return $this->masters->get();
    }
}
