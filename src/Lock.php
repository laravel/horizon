<?php

namespace Laravel\Horizon;

abstract class Lock
{
    /**
     * Execute the given callback if a lock can be acquired.
     *
     * @param  string  $key
     * @param  \Closure  $callback
     * @param  int  $seconds
     * @return void
     */
    public function with($key, $callback, $seconds = 60)
    {
        if ($this->get($key, $seconds)) {
            try {
                call_user_func($callback);
            } finally {
                $this->release($key);
            }
        }
    }

    /**
     * Determine if a lock exists for the given key.
     *
     * @param  string  $key
     * @return bool
     */
    abstract public function exists($key);

    /**
     * Attempt to get a lock for the given key.
     *
     * @param  string  $key
     * @param  int  $seconds
     * @return bool
     */
    abstract public function get($key, $seconds = 60);

    /**
     * Release the lock for the given key.
     *
     * @param  string  $key
     * @return void
     */
    abstract public function release($key);
}
