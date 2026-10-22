<?php

namespace Laravel\Horizon\Jobs;

use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Repositories\RedisMonitoringMetadataRepository;

class MonitorTag
{
    /**
     * Create a new job instance.
     *
     * @param  string  $tag  The tag to monitor.
     * @return void
     */
    public function __construct(
        public $tag,
        public $reason = null,
        public $createdBy = null,
    ) {
    }

    /**
     * Execute the job.
     *
     * @param  \Laravel\Horizon\Contracts\TagRepository  $tags
     * @return void
     */
    public function handle(TagRepository $tags, RedisMonitoringMetadataRepository $metadata)
    {
        if (! in_array($this->tag, $tags->monitoring(), true)) {
            $metadata->create($this->tag, $this->reason, $this->createdBy);
        }

        $tags->monitor($this->tag);
    }
}
