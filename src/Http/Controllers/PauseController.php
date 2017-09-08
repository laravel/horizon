<?php

namespace Laravel\Horizon\Http\Controllers;

use Laravel\Horizon\Contracts\MasterSupervisorRepository;

class PauseController extends Controller
{
    /**
     * Pause horizon
     *
     * @return void
     */
    public function store()
    {
        resolve(MasterSupervisorRepository::class)->pause();
    }

    /**
     * Resume horizon
     *
     * @return void
     */
    public function destroy()
    {
        resolve(MasterSupervisorRepository::class)->resume();
    }
}
