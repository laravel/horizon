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

        $tagJobs = $tags->paginate($this->tag);

        while (count($tagJobs) !== 0) {
            $jobs->deleteMonitored($tagJobs);

            $offset = array_keys($tagJobs)[count($tagJobs) - 1] + 1;
            $tagJobs = $tags->paginate($this->tag, $offset);
        }

        $tags->forget($this->tag);
    }
}
