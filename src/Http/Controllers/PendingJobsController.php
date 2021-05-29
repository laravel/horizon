<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Horizon\Contracts\IndexedJobsRepository;
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
     * The indexed job repository implementation.
     *
     * @var \Laravel\Horizon\Contracts\IndexedJobsRepository
     */
    public $indexedJobs;

    /**
     * Create a new controller instance.
     *
     * @param  \Laravel\Horizon\Contracts\JobRepository  $jobs
     * @param  \Laravel\Horizon\Contracts\IndexedJobsRepository  $indexedJobs
     * @return void
     */
    public function __construct(JobRepository $jobs, IndexedJobsRepository $indexedJobs)
    {
        parent::__construct();

        $this->jobs = $jobs;
        $this->indexedJobs = $indexedJobs;
    }

    /**
     * Get all of the pending jobs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function index(Request $request)
    {
        if ($this->filtersSet($request)) {
            $jobName = addslashes($request->query('job_name'));
            $createdAtFrom = $request->query('created_at_from');
            $createdAtTo = $request->query('created_at_to');
            $startingAt = $request->query('starting_at', -1);

            $jobs = $this->indexedJobs->getIndexedPending($startingAt, $jobName, $createdAtFrom, $createdAtTo, )->map(function ($job) {
                $job->payload = json_decode($job->payload);

                return $job;
            })->values();

        } else {
            $jobs = $this->jobs->getPending($request->query('starting_at', -1))->map(function ($job) {
                $job->payload = json_decode($job->payload);

                return $job;
            })->values();
    }
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
     * Check if filter's params was sent
     */
    protected function filtersSet(Request $request): bool
    {
        $jobName = $request->query('job_name');
        $createdAtFrom = $request->query('created_at_from');
        $createdAtTo = $request->query('created_at_to');

        return !empty($jobName) || !empty($createdAtFrom) || !empty($createdAtTo);
    }
}
