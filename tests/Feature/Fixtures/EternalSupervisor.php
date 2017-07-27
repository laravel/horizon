<?php

namespace Laravel\Horizon\Tests\Feature\Fixtures;

class EternalSupervisor
{
    public $name = 'eternal';

    public function terminate()
    {
        //
    }

    public function isRunning()
    {
        return true;
    }
}
