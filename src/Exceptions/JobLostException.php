<?php

namespace Laravel\Horizon\Exceptions;

use RuntimeException;

class JobLostException extends RuntimeException
{
    /**
     * Create a new exception for a stale reserved Horizon job.
     *
     * @param  string  $jobId
     * @param  string  $connection
     * @return static
     */
    public static function forJob(string $jobId, string $connection): static
    {
        return new static(sprintf(
            'Job [%s] on connection [%s] was lost after being reserved past its retry window.',
            $jobId,
            $connection
        ));
    }
}
