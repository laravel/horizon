<?php

namespace Laravel\Horizon\Tests\Feature\Fakes;

class FakePool
{
    public $queue;
    public $processCount;

    public function __construct($queue, $processCount)
    {
        $this->queue = $queue;
        $this->processCount = $processCount;
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
}
