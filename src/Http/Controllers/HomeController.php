<?php

namespace Laravel\Horizon\Http\Controllers;

use Laravel\Horizon\Horizon;

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
            'cssFile' => Horizon::$useDarkTheme ? 'app-dark.css' : 'app.css',
            'horizonScriptVariables' => Horizon::scriptVariables(),
            'assetsAreCurrent' => Horizon::assetsAreCurrent(),
        ]);
    }
}
