<?php

namespace Laravel\Horizon\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Enums\SupervisorStatus;
use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\Models\HorizonMasterSupervisor;

class DatabaseMasterSupervisorRepository implements MasterSupervisorRepository
{
    /**
     * The number of seconds a master supervisor heartbeat is considered active.
     *
     * @var int
     */
    public const TTL_SECONDS = 15;

    /**
     * Get the names of all the master supervisors currently running.
     *
     * @return array
     */
    public function names()
    {
        return HorizonMasterSupervisor::where('expires_at', '>', CarbonImmutable::now())
            ->orderBy('expires_at', 'desc')
            ->pluck('name')
            ->all();
    }

    /**
     * Get information on all of the master supervisors.
     *
     * @return array
     */
    public function all()
    {
        return $this->get($this->names());
    }

    /**
     * Get information on a master supervisor by name.
     *
     * @param  string  $name
     * @return \stdClass|null
     */
    public function find($name)
    {
        return Arr::get($this->get([$name]), 0);
    }

    /**
     * Get information on the given master supervisors.
     *
     * @param  array  $names
     * @return array
     */
    public function get(array $names)
    {
        if (empty($names)) {
            return [];
        }

        return HorizonMasterSupervisor::whereIn('name', $names)
            ->where('expires_at', '>', CarbonImmutable::now())
            ->get()
            ->map(fn ($master) => (object) [
                'name' => $master->name,
                'environment' => $master->environment,
                'pid' => $master->pid,
                'status' => $master->status?->value,
                'supervisors' => $master->supervisors ?? [],
            ])
            ->values()
            ->all();
    }

    /**
     * Update the information about the given master supervisor.
     *
     * @param  \Laravel\Horizon\MasterSupervisor  $master
     * @return void
     */
    public function update(MasterSupervisor $master)
    {
        $now = CarbonImmutable::now();

        HorizonMasterSupervisor::upsert([[
            'name' => $master->name,
            'environment' => $master->environment,
            'pid' => $master->pid(),
            'status' => $master->working ? SupervisorStatus::Running->value : SupervisorStatus::Paused->value,
            'supervisors' => json_encode($master->supervisors->map->name->all()),
            'expires_at' => $now->addSeconds(self::TTL_SECONDS),
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['name'], ['environment', 'pid', 'status', 'supervisors', 'expires_at', 'updated_at']);
    }

    /**
     * Remove the master supervisor information from storage.
     *
     * @param  string  $name
     * @return void
     */
    public function forget($name)
    {
        if (! $master = $this->find($name)) {
            return;
        }

        app(SupervisorRepository::class)->forget($master->supervisors);

        HorizonMasterSupervisor::where('name', $name)->delete();
    }

    /**
     * Remove expired master supervisors from storage.
     *
     * @return void
     */
    public function flushExpired()
    {
        HorizonMasterSupervisor::where('expires_at', '<=', CarbonImmutable::now())->delete();
    }
}
