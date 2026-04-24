<?php

namespace Laravel\Horizon\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Enums\SupervisorStatus;
use Laravel\Horizon\Models\HorizonSupervisor;
use Laravel\Horizon\Supervisor;

class DatabaseSupervisorRepository implements SupervisorRepository
{
    /**
     * The number of seconds a supervisor heartbeat is considered active.
     *
     * @var int
     */
    public const TTL_SECONDS = 30;

    /**
     * Get the names of all the supervisors currently running.
     *
     * @return array
     */
    public function names()
    {
        return HorizonSupervisor::where('expires_at', '>', CarbonImmutable::now())
            ->orderBy('expires_at', 'desc')
            ->pluck('name')
            ->all();
    }

    /**
     * Get information on all of the supervisors.
     *
     * @return array
     */
    public function all()
    {
        return $this->get($this->names());
    }

    /**
     * Get information on a supervisor by name.
     *
     * @param  string  $name
     * @return \stdClass|null
     */
    public function find($name)
    {
        return Arr::get($this->get([$name]), 0);
    }

    /**
     * Get information on the given supervisors.
     *
     * @param  array  $names
     * @return array
     */
    public function get(array $names)
    {
        if (empty($names)) {
            return [];
        }

        return HorizonSupervisor::whereIn('name', $names)
            ->where('expires_at', '>', CarbonImmutable::now())
            ->get()
            ->map(fn ($supervisor) => (object) [
                'name' => $supervisor->name,
                'master' => $supervisor->master,
                'pid' => $supervisor->pid,
                'status' => $supervisor->status?->value,
                'processes' => $supervisor->processes ?? [],
                'options' => $supervisor->options ?? [],
            ])
            ->values()
            ->all();
    }

    /**
     * Get the longest active timeout setting for a supervisor.
     *
     * @return int
     */
    public function longestActiveTimeout()
    {
        return collect($this->all())
            ->max(fn ($supervisor) => $supervisor->options['timeout'] ?? 0) ?: 0;
    }

    /**
     * Update the information about the given supervisor process.
     *
     * @param  \Laravel\Horizon\Supervisor  $supervisor
     * @return void
     */
    public function update(Supervisor $supervisor)
    {
        $processes = $supervisor->processPools
            ->mapWithKeys(fn ($pool) => [$supervisor->options->connection.':'.$pool->queue() => count($pool->processes())])
            ->all();

        $now = CarbonImmutable::now();

        HorizonSupervisor::upsert([[
            'name' => $supervisor->name,
            'master' => implode(':', explode(':', $supervisor->name, -1)),
            'pid' => $supervisor->pid(),
            'status' => $supervisor->working ? SupervisorStatus::Running->value : SupervisorStatus::Paused->value,
            'processes' => json_encode($processes),
            'options' => json_encode($supervisor->options->toArray()),
            'expires_at' => $now->addSeconds(self::TTL_SECONDS),
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['name'], ['master', 'pid', 'status', 'processes', 'options', 'expires_at', 'updated_at']);
    }

    /**
     * Remove the supervisor information from storage.
     *
     * @param  array|string  $names
     * @return void
     */
    public function forget($names)
    {
        $names = (array) $names;

        if (empty($names)) {
            return;
        }

        HorizonSupervisor::whereIn('name', $names)->delete();
    }

    /**
     * Remove expired supervisors from storage.
     *
     * @return void
     */
    public function flushExpired()
    {
        HorizonSupervisor::where('expires_at', '<=', CarbonImmutable::now())->delete();
    }
}
