<?php

namespace Laravel\Horizon\Events;

class DashboardActionPerformed
{
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  string|null  $action
     * @param  array  $parameters
     * @param  array  $input
     * @return void
     */
    public function __construct(
        public $user,
        public $action,
        public array $parameters = [],
        public array $input = [],
    ) {
        //
    }
}
