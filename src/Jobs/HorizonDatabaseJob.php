<?php

namespace Laravel\Horizon\Jobs;

use Illuminate\Queue\Jobs\DatabaseJob;
use Laravel\Horizon\DatabaseQueue;

class HorizonDatabaseJob extends DatabaseJob
{
    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    #[\Override]
    public function delete()
    {
        parent::delete();

        if ($this->database instanceof DatabaseQueue) {
            $this->database->fireJobDeletedEvent($this->queue, $this);
        }
    }
}
