<?php

namespace Laravel\Horizon\Contracts;

use Laravel\Horizon\Supervisor;

interface SupervisorRepository
{
    /**
     * Get the names of all the supervisors currently running.
     *
     * @return array
     */
    public function names();

    /**
     * Get information on all of the supervisors.
     *
     * @return array
     */
    public function all();

    /**
     * Get information on a supervisor by name.
     *
     * @param  string  $name
     * @return array
     */
    public function find($name);

    /**
     * Get information on the given supervisors.
     *
     * @param  array  $names
     * @return array
     */
    public function get(array $names);

    /**
     * Get the longest active timeout setting for a supervisor.
     *
     * @return int
     */
    public function longestActiveTimeout();

    /**
     * Update the information about the given supervisor process.
     *
     * @param  \Laravel\Horizon\Supervisor  $supervisor
     * @return void
     */
    public function update(Supervisor $supervisor);

    /**
     * Remove the supervisor information from storage.
     *
     * @param  array|string  $names
     * @return void
     */
    public function forget($names);

    /**
     * Remove expired supervisors from storage.
     *
     * @return void
     */
    public function flushExpired();
}
