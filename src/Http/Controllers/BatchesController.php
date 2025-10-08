<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Bus\BatchRepository;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Jobs\RetryFailedJob;

class BatchesController extends Controller
{
    /**
     * Get all of the batches.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Bus\BatchRepository  $repository
     * @return array
     */
    public function index(Request $request, BatchRepository $repository)
    {
        try {
            $batches = $repository->get(50, $request->query('before_id') ?: null);
        } catch (QueryException $e) {
            $batches = [];
        }

        return Inertia::render('Horizone.Batches.Index', [
            'batches' => Inertia::merge($batches),
        ]);
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

        if ($batch) {
            $failedJobs = app(JobRepository::class)
                            ->getJobs($batch->failedJobIds);
        }

        return [
            'batch' => $batch,
            'failedJobs' => $failedJobs ?? null,
        ];
    }

    /**
     * Retry the given batch.
     *
     * @param  string  $id
     * @return void
     */
    public function retry($id)
    {
        $batch = $this->batches->find($id);

        if ($batch) {
            app(JobRepository::class)
                ->getJobs($batch->failedJobIds)
                ->reject(function ($job) {
                    $payload = json_decode($job->payload);

                    return isset($payload->retry_of);
                })
                ->each(function ($job) {
                    dispatch(new RetryFailedJob($job->id));
                });
        }
    }
}
