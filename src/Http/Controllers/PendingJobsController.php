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
        $queue = $request->query('queue');

        if ($queue) {
            return $this->paginateByQueue($request, $queue);
        }

        $jobs = $this->jobs
            ->getPending($request->query('starting_at', -1))
            ->map(fn ($job) => $this->decode($job))
            ->values();

        return [
            'jobs' => $jobs,
            'total' => $this->jobs->countPending(),
        ];
    }

    /**
     * Paginate pending jobs filtered by queue.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $queue
     * @return array
     */
    protected function paginateByQueue(Request $request, $queue)
    {
        $startingAt = (int) $request->query('starting_at', -1);
        $limit = 50;

        $filteredJobs = collect();
        $total = 0;
        $index = -1;
        $skipped = 0;

        while (true) {
            $batch = $this->jobs->getPending($index);

            if ($batch->isEmpty()) {
                break;
            }

            $matchingJobs = $batch->filter(fn ($job) => $job->queue === $queue);

            $total += $matchingJobs->count();

            foreach ($matchingJobs as $job) {
                if ($skipped <= $startingAt) {
                    $skipped++;
                    continue;
                }

                if ($filteredJobs->count() < $limit) {
                    $job->index = $skipped;
                    $skipped++;
                    $filteredJobs->push($job);
                }
            }

            $index = $batch->last()->index ?? $index + 50;
        }

        return [
            'jobs' => $filteredJobs->map(fn ($job) => $this->decode($job))->values(),
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
