<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\PendingJobsRepository;

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
        $jobs = $this->jobs->getPending($request->query('starting_at', -1))->map(function ($job) {
            $job->payload = json_decode($job->payload);

            return $job;
        })->values();

        return [
            'jobs' => $jobs,
            'total' => $this->jobs->countPending(),
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

    /**
     * Delete pending jobs.
     *
     * @param \Illuminate\Http\Request $request
     * @param PendingJobsRepository $pendingJobs
     * @return void
     */
    public function batchDelete(Request $request, PendingJobsRepository $pendingJobs)
    {
        $pendingJobs->deleteByIds($request->all());
    }
}
