<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Horizon\Contracts\JobRepository;

class PendingJobsController extends Controller
{
    /**
     * The job repository implementation.
     *
     * @var \Laravel\Horizon\Contracts\JobRepository
     */
    public $jobs;

    /**
     * Create a new controller instance.
     *
     * @param  \Laravel\Horizon\Contracts\JobRepository  $jobs
     * @return void
     */
    public function __construct(JobRepository $jobs)
    {
        parent::__construct();

        $this->jobs = $jobs;
    }

    /**
     * Get all of the pending jobs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function index(Request $request)
    {
        $from = $request->query('date_from');  // örnek: '2023-07-01'
        $to = $request->query('date_to');      // örnek: '2023-07-31'
        $starting = $request->query('starting_at', -1);

        if (config('horizon.search_by_date') && ($from || $to)) {
            $jobs = $this->jobs->getPendingByDateRange($starting, $from, $to);
            $total = $this->jobs->countPendingByDateRange($from, $to);
        } else {
            $jobs = $this->jobs->getPending($starting);
            $total = $this->jobs->countPending();
        }

        $jobs = collect($jobs)->map(function ($job) {
            $job->payload = json_decode($job->payload);
            return $job;
        })->values();

        return [
            'jobs' => $jobs,
            'total' => $total,
        ];
    }

    /**
     * Decode the given job.
     *
     * @param  object  $job
     * @return object
     */
    protected function decode($job)
    {
        $job->payload = json_decode($job->payload);

        return $job;
    }
}
