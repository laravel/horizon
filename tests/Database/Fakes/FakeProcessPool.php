<?php

namespace Laravel\Horizon\Tests\Database\Fakes;

class FakeProcessPool
{
    /**
     * The queue name handled by the pool.
     *
     * @var string
     */
    public $queue;

    /**
     * The fake processes contained in the pool.
     *
     * @var array
     */
    public $processes;

    /**
     * Create a new fake process pool.
     *
     * @param  string  $queue
     * @param  int  $processCount
     * @return void
     */
    public function __construct($queue, $processCount = 0)
    {
        $this->queue = $queue;
        $this->processes = array_fill(0, $processCount, true);
    }

    /**
     * Get the queue name.
     *
     * @return string
     */
    public function queue()
    {
        return $this->queue;
    }

    /**
     * Get the processes contained in the pool.
     *
     * @return array
     */
    public function processes()
    {
        return $this->processes;
    }
}
