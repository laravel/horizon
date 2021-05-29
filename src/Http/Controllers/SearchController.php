<?php

declare(strict_types=1);

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\IndexedJobsRepository;

class SearchController extends Controller
{
    public function searchJobsByName(Request $request, IndexedJobsRepository $repository)
    {
        $status = $request->get('status');
        $jobName = addslashes($request->get('name'));

        return collect($repository->getKeysByJobNameAndStatus("*{$jobName}*", $status))
            ->slice(0, 5)
            ->map(function (string $key) {
                $indexPrefix = config('horizon.prefix_index', 'index');

                return Str::after($key, ":{$indexPrefix}");
            });
    }
}
