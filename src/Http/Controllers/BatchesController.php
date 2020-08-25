<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Bus\BatchRepository;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Jobs\RetryFailedJob;

class BatchesController extends Controller
{
    /**
     * The job repository implementation.
     *
     * @var \Illuminate\Bus\BatchRepository
     */
    public $batches;

    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Bus\BatchRepository  $batches
     * @return void
     */
    public function __construct(BatchRepository $batches)
    {
        parent::__construct();

        $this->batches = $batches;
    }

    /**
     * Get all of the batches.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function index(Request $request)
    {
        try {
            $batches = $this->batches->get(50, $request->query('before_id') ?: null);
        } catch (QueryException $e) {
            $batches = [];
        }

        return [
            'batches' => $batches,
        ];
    }

    /**
     * Get the details of a batch by ID.
     *
     * @param  string  $id
     * @return array
     */
    public function show($id)
    {
        $batch = $this->batches->find($id);

        $failedJobs = app(JobRepository::class)
                        ->getJobs($batch->failedJobIds);

        return [
            'batch' => $batch,
            'failedJobs' => $failedJobs,
        ];
    }

    /**
     * Retry the given batch.
     *
     * @param  string  $id
     * @return array
     */
    public function retry($id)
    {
        $batch = $this->batches->find($id);

        app(JobRepository::class)
                        ->getJobs($batch->failedJobIds)
                        ->reject(function ($job) {
                            $payload = json_decode($job->payload);

                            return isset($payload->retry_of);
                        })->each(function ($job) {
                            dispatch(new RetryFailedJob($job->id));
                        });
    }
}
