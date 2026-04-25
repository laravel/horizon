<?php

namespace Laravel\Horizon\Tests\Database\Fakes;

use Laravel\Horizon\Supervisor;

class SupervisorWithFakePool extends Supervisor
{
    /**
     * Create a single process pool using the test fake.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function createSingleProcessPool()
    {
        return collect([new FakeProcessPool($this->options->queue)]);
    }

    /**
     * Create a process pool per queue using the test fake.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function createProcessPoolPerQueue()
    {
        return collect(explode(',', $this->options->queue))->map(function ($queue) {
            return new FakeProcessPool($queue);
        });
    }
}
