<?php

namespace Laravel\Horizon;

use Symfony\Component\Process\Process;

class BackgroundProcess extends Process
{
    /**
     * Destruct the object.
     *
     * @return void
     */
    public function __destruct()
    {
        //
    }
}
