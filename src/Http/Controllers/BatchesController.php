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
            return [
                'batches' => [],
                'available' => false,
            ];
        }

        return [
            'batches' => $batches,
            'available' => true,
        ];
    }

    /**
     * Get the latest active batches for the dashboard overview.
     *
     * @return array
     */
    public function overview()
    {
        try {
            if (! $this->supportsDatabaseBatchQueries()) {
                return $this->unavailableBatchOverview();
            }

            return [
                'available' => true,
                'previews' => $this->activeBatchPreviews(),
            ];
        } catch (QueryException $e) {
            return $this->unavailableBatchOverview();
        }
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
     * Determine whether configured batch storage can be queried as a database table.
     *
     * @return bool
     */
    protected function supportsDatabaseBatchQueries()
    {
        return config('queue.batching.driver', 'database') !== 'dynamodb';
    }

    /**
     * Build a query of active batches.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function activeBatchesQuery()
    {
        return DB::connection(config('queue.batching.database'))
            ->table(config('queue.batching.table', 'job_batches'))
            ->whereNull('cancelled_at')
            ->whereColumn('pending_jobs', '>', 'failed_jobs');
    }

    /**
     * Get the three latest active batches for the dashboard card.
     *
     * @return array
     */
    protected function activeBatchPreviews()
    {
        return $this->activeBatchesQuery()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get(['id', 'name', 'total_jobs', 'pending_jobs'])
            ->map(function ($batch) {
                $name = trim((string) $batch->name);

                return [
                    'id' => $batch->id,
                    'name' => $name === '' ? $batch->id : $name,
                    'progress' => $batch->total_jobs > 0
                        ? (int) round((($batch->total_jobs - $batch->pending_jobs) / $batch->total_jobs) * 100)
                        : 0,
                ];
            })
            ->all();
    }

    /**
     * Get the unavailable batch overview shape.
     *
     * @return array
     */
    protected function unavailableBatchOverview()
    {
        return [
            'available' => false,
            'previews' => [],
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
