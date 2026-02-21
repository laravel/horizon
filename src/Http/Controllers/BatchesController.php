<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Bus\BatchRepository;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Jobs\RetryFailedJob;

class BatchesController extends Controller
{
    /**
     * The batch repository implementation.
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
            $batches = $request->query('query')
                ? $this->searchBatches($request)
                : $this->batches->get(50, $request->query('before_id'));
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
     * Search the batches by name or ID.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    private function searchBatches(Request $request)
    {
        $query = str_replace(['%', '_'], ['\%', '\_'], $request->query('query'));

        return DB::connection(config('queue.batching.database'))
            ->table(config('queue.batching.table', 'job_batches'))
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('id', 'like', "%{$query}%");
            })
            ->orderByDesc('id')
            ->limit(50)
            ->when($request->query('before_id'), fn ($q, $beforeId) => $q->where('id', '<', $beforeId))
            ->pluck('id')
            ->map(fn ($id) => $this->batches->find($id))
            ->filter()
            ->values()
            ->all();
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
