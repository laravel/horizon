<?php

namespace Laravel\Horizon\Jobs;

use Illuminate\Queue\Jobs\DatabaseJob as BaseDatabaseJob;
use Laravel\Horizon\Events\JobDeleted;
use Laravel\Horizon\Events\JobReleased;

class DatabaseJob extends BaseDatabaseJob
{
    /**
     * Release the job back into the queue after (n) seconds.
     *
     * @param  int  $delay
     * @return void
     */
    public function release($delay = 0)
    {
        parent::release($delay);

        $this->database->event($this->queue, new JobReleased($this->job->payload));
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        parent::delete();

        $this->database->event($this->queue, new JobDeleted($this, $this->job->payload));
    }
}
