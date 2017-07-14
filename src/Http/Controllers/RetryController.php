<?php

namespace Laravel\Horizon\Http\Controllers;

use Laravel\Horizon\Jobs\RetryFailedJob;

class RetryController extends Controller
{
    /**
     * Retry a failed job.
     *
     * @return \Illuminate\Http\Response
     */
    public function store($id)
    {
        dispatch(new RetryFailedJob($id));
    }
}
