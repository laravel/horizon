<?php

namespace Laravel\Horizon;

class PhpBinary
{
    /**
     * Determine the proper PHP executable.
     *
     * @return string
     */
    public static function getPath()
    {
        $escape = '\\' === DIRECTORY_SEPARATOR ? '"' : '\'';

        return $escape.PHP_BINARY.$escape;
    }
}
