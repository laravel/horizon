<?php

namespace Laravel\Horizon\Http\Controllers;

use Laravel\Horizon\Http\Middleware\Authenticate;
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
        $this->middleware(Authenticate::class);
    }

    /**
     * Check payload limits for job reports
     *
     * @param $job
     */
    public function checkPayload(&$job) {
        $payload_size = ini_get('mbstring.func_overload') ? mb_strlen($job->payload , '8bit') : strlen($job->payload);
        if($payload_size > config('horizon.payload')) {
            $job->payload = 'Payload Size Exceeded';
        } else {
            $job->payload = json_decode($job->payload);
        }
    }
}
