<?php

namespace Laravel\Horizon\Jobs;

use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\TagRepository;

class StopMonitoringTag
{
    /**
     * The tag to stop monitoring.
     *
     * @var string
     */
    public $tag;

    /**
     * Create a new job instance.
     *
     * @param  string  $tag
     * @return void
     */
    public function __construct($tag)
    {
        $this->tag = $tag;
    }

    /**
     * Execute the job.
     *
     * @param  \Laravel\Horizon\Contracts\JobRepository  $jobs
     * @param  \Laravel\Horizon\Contracts\TagRepository  $tags
     * @return void
     */
    public function handle(JobRepository $jobs, TagRepository $tags)
    {
        $tags->stopMonitoring($this->tag);

        $monitored = $tags->paginate($this->tag);

        while (count($monitored) > 0) {
            $jobs->deleteMonitored($monitored);

            $offset = array_keys($monitored)[count($monitored) - 1] + 1;

            $monitored = $tags->paginate($this->tag, $offset);
        }

        $tags->forget($this->tag);
    }
}
