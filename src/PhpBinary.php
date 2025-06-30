<?php

namespace Laravel\Horizon;

class PhpBinary
{
    /**
     * Get the path to the PHP executable.
     *
     * @return string
     */
    public static function path()
    {
        return env('PHP_BINARY', escapeshellcmd(PHP_BINARY));
    }
}
