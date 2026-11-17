<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Horizon\Jobs\RetryFailedJob;

class RetryController extends Controller
{
    /**
     * Retry a failed job.
     *
     * @param  string  $id
     * @return void
     */
    public function store($id)
    {
        dispatch(new RetryFailedJob($id));
    }

    /**
     * Retry the given failed jobs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array{retried: int}
     */
    public function storeMany(Request $request)
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'max:1000'],
            'ids.*' => ['required', 'string', 'distinct'],
        ])['ids'];

        foreach ($ids as $id) {
            dispatch(new RetryFailedJob($id));
        }

        return ['retried' => count($ids)];
    }
}
