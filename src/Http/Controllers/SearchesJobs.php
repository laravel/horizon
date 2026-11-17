<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Http\Request;

trait SearchesJobs
{
    /**
     * Search the jobs of the given type.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $type
     * @return array
     */
    protected function search(Request $request, $type)
    {
        $results = $this->jobs->search(
            $type, (string) $request->query('search'), (int) $request->query('starting_at', -1)
        );

        return [
            'jobs' => $results['jobs']->map(fn ($job) => $this->decode($job))->values(),
            'next' => $results['next'],
        ];
    }
}
