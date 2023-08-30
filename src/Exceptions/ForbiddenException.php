<?php

namespace Laravel\Horizon\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ForbiddenException extends HttpException
{
    /**
     * Make a new exception
     *
     * @return static
     */
    public static function make()
    {
        return new static(403);
    }
}
