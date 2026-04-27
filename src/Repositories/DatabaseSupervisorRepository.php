<?php

namespace Laravel\Horizon\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Arr;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Supervisor;

class DatabaseSupervisorRepository implements SupervisorRepository
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
     * Get the names of all the supervisors currently running.
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
        return Arr::first($this->get([$name]));
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

        return $this->table()
            ->whereIn('name', $names)
            ->get()
            ->map(function ($record) {
                return (object) [
                    'name' => $record->name,
                    'master' => $record->master,
                    'pid' => $record->pid,
                    'status' => $record->status,
                    'processes' => json_decode($record->processes, true),
                    'options' => json_decode($record->options, true),
                ];
            })->all();
    }

    /**
     * Get the longest active timeout setting for a supervisor.
     *
     * @return int
     */
    public function longestActiveTimeout()
    {
        return collect($this->all())->max(function ($supervisor) {
            return $supervisor->options['timeout'];
        }) ?: 0;
    }

    /**
     * Update the information about the given supervisor process.
     *
     * @param  \Laravel\Horizon\Supervisor  $supervisor
     * @return void
     */
    public function update(Supervisor $supervisor)
    {
        $processes = $supervisor->processPools->mapWithKeys(function ($pool) use ($supervisor) {
            return [$supervisor->options->connection.':'.$pool->queue() => count($pool->processes())];
        })->toJson();

        $this->table()->updateOrInsert(['name' => $supervisor->name], [
            'master' => implode(':', explode(':', $supervisor->name, -1)),
            'pid' => $supervisor->pid(),
            'status' => $supervisor->working ? 'running' : 'paused',
            'processes' => $processes,
            'options' => $supervisor->options->toJson(),
            'expires_at' => CarbonImmutable::now()->addSeconds(30)->getTimestamp(),
            'updated_at' => CarbonImmutable::now()->getTimestamp(),
        ]);
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

        $this->table()->whereIn('name', $names)->delete();
    }

    /**
     * Remove expired supervisors from storage.
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
     * Get a query builder for the horizon supervisors table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        return $this->connection()->table('horizon_supervisors');
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
