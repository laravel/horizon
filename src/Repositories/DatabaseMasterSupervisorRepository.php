<?php

namespace Laravel\Horizon\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Arr;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\MasterSupervisor;

class DatabaseMasterSupervisorRepository implements MasterSupervisorRepository
{
    /**
     * The database connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    public $resolver;

    /**
     * Create a new repository instance.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @return void
     */
    public function __construct(ConnectionResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Get the names of all the master supervisors currently running.
     *
     * @return array
     */
    public function names()
    {
        return $this->table()
            ->where('expires_at', '>=', CarbonImmutable::now()->getTimestamp())
            ->orderBy('updated_at', 'desc')
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

        return $this->table()
            ->whereIn('name', $names)
            ->get()
            ->map(function ($record) {
                return (object) [
                    'name' => $record->name,
                    'pid' => $record->pid,
                    'status' => $record->status,
                    'supervisors' => json_decode($record->supervisors, true),
                ];
            })->all();
    }

    /**
     * Update the information about the given master supervisor.
     *
     * @param  \Laravel\Horizon\MasterSupervisor  $master
     * @return void
     */
    public function update(MasterSupervisor $master)
    {
        $supervisors = $master->supervisors->map->name->all();

        $this->table()->updateOrInsert(['name' => $master->name], [
            'pid' => $master->pid(),
            'status' => $master->working ? 'running' : 'paused',
            'supervisors' => json_encode($supervisors),
            'expires_at' => CarbonImmutable::now()->addSeconds(15)->getTimestamp(),
            'updated_at' => CarbonImmutable::now(),
        ]);
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

        app(SupervisorRepository::class)->forget(
            $master->supervisors
        );

        $this->table()->where('name', $name)->delete();
    }

    /**
     * Remove expired master supervisors from storage.
     *
     * @return void
     */
    public function flushExpired()
    {
        $this->table()
            ->where('expires_at', '<', CarbonImmutable::now()->getTimestamp())
            ->delete();
    }

    /**
     * Get a query builder for the horizon master supervisors table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        return $this->connection()->table('horizon_master_supervisors');
    }

    /**
     * Get the database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection()
    {
        return $this->resolver->connection(config('horizon.database.connection'));
    }
}
