<?php

namespace Laravel\Horizon;

class SupervisorCommandString
{
    /**
     * The base worker command.
     *
     * @var string
     */
    public static $command = 'exec @php artisan horizon:supervisor';

    /**
     * Get the command-line representation of the options for a supervisor.
     *
     * @param  \Laravel\Horizon\SupervisorOptions  $options
     * @return string
     */
    public static function fromOptions(SupervisorOptions $options)
    {
        $command = str_replace('@php', PhpBinary::path(), static::$command);

        return sprintf(
            "%s {$options->name} {$options->connection} %s",
            $command,
            static::toOptionsString($options)
        );
    }

    /**
     * Get the additional option string for the command.
     *
     * @param  \Laravel\Horizon\SupervisorOptions  $options
     * @return string
     */
    public static function toOptionsString(SupervisorOptions $options)
    {
        return sprintf('%s --balance=%s --max-processes=%s --min-processes=%s --nice=%s',
            QueueCommandString::toOptionsString($options), $options->balance,
            $options->maxProcesses, $options->minProcesses, $options->nice
        );
    }

    /**
     * Reset the base command back to its default value.
     *
     * @return void
     */
    public static function reset()
    {
        static::$command = 'exec @php artisan horizon:supervisor';
    }
}
