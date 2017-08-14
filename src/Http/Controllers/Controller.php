<?php

namespace Laravel\Horizon\Http\Controllers;

use Laravel\Horizon\Horizon;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(Horizon::middleware());
    }
}
