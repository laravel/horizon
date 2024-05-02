<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Support\Facades\App;

class HomeController extends Controller
{
    /**
     * Single page application catch-all route.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('horizon::layout', [
            'isDownForMaintenance' => App::isDownForMaintenance(),
        ]);
    }
}
