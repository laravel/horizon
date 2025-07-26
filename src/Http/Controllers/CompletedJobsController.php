<?php

namespace Laravel\Horizon\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Horizon\Contracts\JobRepository;

class CompletedJobsController extends Controller
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
     * Get all of the completed jobs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */

    public function index(Request $request)
    {
        $from = $request->query('date_from');  // '2023-07-01'
        $to = $request->query('date_to');      // '2023-07-31'

        $starting = $request->query('starting_at', -1);

        if (config('horizon.search_by_date') && ($from || $to)) {
            $jobs = $this->jobs->getCompletedByDateRange($starting, $from, $to);
            $total = $this->jobs->countCompletedByDateRange($from, $to);
        } else {
            $jobs = $this->jobs->getCompleted($starting);
            $total = $this->jobs->countCompleted();
        }

        // Eğer toplam sayıyı da istiyorsanız, count metodunu da eklemelisiniz.

        return [
            'jobs' => $jobs,
            'total' => count($jobs), // Basit sayım, paginasyon için iyileştirilebilir
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
