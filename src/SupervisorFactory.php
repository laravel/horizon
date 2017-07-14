<?php

namespace Laravel\Horizon;

class SupervisorFactory
{
    /**
     * Create a new supervisor instance.
     *
     * @param  SupervisorOptions  $options
     * @return void
     */
    public function make(SupervisorOptions $options)
    {
        return new Supervisor($options);
    }
}
