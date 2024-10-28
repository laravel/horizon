<?php

namespace Laravel\Horizon;

use Illuminate\Support\ProcessUtils;

class PhpBinary
{
    /**
     * Get the path to the PHP executable.
     *
     * @return string
     */
    public static function path()
    {
        if (function_exists('Illuminate\Support\php_binary')) {
            return ProcessUtils::escapeArgument(\Illuminate\Support\php_binary());
        }

        return ProcessUtils::escapeArgument(PHP_BINARY);
    }
}
