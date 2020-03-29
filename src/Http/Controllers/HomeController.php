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
        $view = config('horizon.view')?config('horizon.view'):'horizon::layout';
        return view($view, [
            'cssFile' => Horizon::$useDarkTheme ? 'app-dark.css' : 'app.css',
            'horizonScriptVariables' => Horizon::scriptVariables(),
            'assetsAreCurrent' => Horizon::assetsAreCurrent(),
        ]);
    }
}
