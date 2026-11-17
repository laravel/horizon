<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\RedisQueue;

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
        $jobs = $this->jobs
            ->getPending($request->query('starting_at', -1))
            ->map(function ($job) {
                $job->payload = json_decode($job->payload);

                return $job;
            })
            ->values();

        return [
            'jobs' => $jobs,
            'total' => $this->jobs->countPending(),
        ];
    }

    /**
     * Delete the given pending job from its queue.
     *
     * @param  string  $id
     * @return void
     */
    public function destroy($id)
    {
        $job = $this->findPending($id);

        abort_unless(
            $this->queue($job)->deletePending($job->queue, $job->payload), 409, 'The job is no longer pending.'
        );

        $this->jobs->deletePending($id);
    }

    /**
     * Push the given delayed job onto its queue so it is processed immediately.
     *
     * @param  string  $id
     * @return void
     */
    public function run($id)
    {
        $job = $this->findPending($id);

        abort_unless(
            $this->queue($job)->runDelayedNow($job->queue, $job->payload), 409, 'The job is not delayed.'
        );
    }

    /**
     * Find the pending job with the given ID.
     *
     * @param  string  $id
     * @return object
     */
    protected function findPending($id)
    {
        $job = $this->jobs->getJobs([$id])->first();

        abort_unless($job && $job->status === 'pending', 404);

        return $job;
    }

    /**
     * Get the queue connection the given job was pushed to.
     *
     * @param  object  $job
     * @return \Laravel\Horizon\RedisQueue
     */
    protected function queue($job)
    {
        $queue = app('queue')->connection($job->connection);

        abort_unless($queue instanceof RedisQueue, 404);

        return $queue;
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
