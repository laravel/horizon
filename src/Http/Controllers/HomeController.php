<?php

namespace Laravel\Horizon\Http\Controllers;

use Laravel\Horizon\Horizon;

class HomeController extends Controller
{
    /**
     * Single page application catch-all route.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('horizon::app', [
            'horizonScriptVariables' => Horizon::scriptVariables(),
        ]);
    }
}
