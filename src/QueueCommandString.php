<?php

namespace Laravel\Horizon;

class QueueCommandString
{
    /**
     * Get the additional option string for the command.
     *
     * @param  \Laravel\Horizon\SupervisorOptions  $options
     * @param  bool  $paused
     * @return string
     */
    public static function toOptionsString(SupervisorOptions $options, $paused = false)
    {
        $string = sprintf('--backoff=%s --memory=%s --queue="%s" --sleep=%s --timeout=%s --tries=%s',
            $options->backoff, $options->memory, $options->queue,
            $options->sleep, $options->timeout, $options->maxTries
        );

        if ($options->force) {
            $string .= ' --force';
        }

        if ($paused) {
            $string .= ' --paused';
        }

        return $string;
    }
}
