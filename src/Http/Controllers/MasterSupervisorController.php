<?php

namespace Laravel\Horizon\Http\Controllers;

use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;

class MasterSupervisorController extends Controller
{
    /**
     * Get all of the master supervisors and their underlying supervisors.
     *
     * @param  MasterSupervisorRepository  $masters
     * @param  SupervisorRepository  $supervisors
     * @return \Illuminate\Http\Response
     */
    public function index(MasterSupervisorRepository $masters,
                          SupervisorRepository $supervisors)
    {
        $masters = collect($masters->all())->keyBy('name')->sortBy('name');

        $supervisors = collect($supervisors->all())->sortBy('name')->groupBy('master');

        return $masters->each(function ($master, $name) use ($supervisors) {
            $master->supervisors = $supervisors->get($name);
        });
    }
}
