<?php

namespace Laravel\Horizon\Tests\Feature\Fakes;

class FakePool
{
    public $queue;
    public $processCount;
    public $maxProcesses;

    public function __construct($queue, $processCount, $max = null)
    {
        $this->queue = $queue;
        $this->processCount = $processCount;
        $this->maxProcesses = $max;
    }

    public function scale($processCount)
    {
        $this->processCount = $processCount;
    }

    public function queue()
    {
        return $this->queue;
    }

    public function pruneTerminatingProcesses()
    {
        //
    }

    public function totalProcessCount()
    {
        return $this->processCount;
    }

    public function maxProcessesPerQueue()
    {
        return $this->maxProcesses;
    }
}
