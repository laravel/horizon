<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Support\Facades\App;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Single page application catch-all route.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function __invoke()
    {
        return Inertia::render('horizon::layout', [
            'isDownForMaintenance' => App::isDownForMaintenance(),
        ]);
    }
}
