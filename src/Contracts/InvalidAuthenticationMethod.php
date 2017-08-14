<?php

namespace Laravel\Horizon\Contracts;

use Exception;

class InvalidAuthenticationMethod extends Exception
{
    public function __construct()
    {
        $this->message = 'You may not use Horizon::check() when using middleware authentication.';
    }
}
