<?php

namespace Laravel\Horizon;

class JobId
{
    /**
     * The custom job ID generator.
     *
     * @var \Closure
     */
    protected static $generator;

    /**
     * Generate a new job ID.
     *
     * @return string
     */
    public static function generate()
    {
        if (isset(static::$generator)) {
            return call_user_func(static::$generator);
        }

        return resolve(Contracts\JobRepository::class)->nextJobId();
    }

    /**
     * Define a custom job ID generator.
     *
     * @param  \Closure|null  $callback
     */
    public static function generateUsing($callback)
    {
        static::$generator = $callback;
    }
}
