<?php

declare(strict_types=1);

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Horizon\Contracts\IndexedJobsRepository;

class SearchController extends Controller
{
    public function searchJobsByName(Request $request, IndexedJobsRepository $repository)
    {
        $status = $request->get('status');
        $jobName = $request->get('name');

        return $repository->getKeysByJobNameAndStatus($jobName, $status);
    }
}
