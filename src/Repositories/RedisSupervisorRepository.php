<?php

namespace Laravel\Horizon\Repositories;

use Illuminate\Support\Arr;
use Laravel\Horizon\Supervisor;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;

class RedisSupervisorRepository implements SupervisorRepository
{
    /**
     * The Redis connection instance.
     *
     * @var RedisFactory
     */
    public $redis;

    /**
     * Create a new repository instance.
     *
     * @param  RedisFactory
     * @return void
     */
    public function __construct(RedisFactory $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Get the names of all the supervisors currently running.
     *
     * @return array
     */
    public function names()
    {
        return collect($this->connection()->keys('supervisor:*'))->map(function ($name) {
            return substr($name, 11);
        })->all();
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
     * @return \StdClass|null
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
        $records = $this->connection()->pipeline(function ($pipe) use ($names) {
            foreach ($names as $name) {
                $pipe->hmget('supervisor:'.$name, 'name', 'master', 'pid', 'status', 'processes', 'options');
            }
        });

        return collect($records)->filter()->map(function ($record) {
            return is_null($record[0]) ? null : (object) [
                'name' => $record[0],
                'master' => $record[1],
                'pid' => $record[2],
                'status' => $record[3],
                'processes' => json_decode($record[4], true),
                'options' => json_decode($record[5], true),
            ];
        })->filter()->all();
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
     * @param  Supervisor  $supervisor
     * @return void
     */
    public function update(Supervisor $supervisor)
    {
        $processes = $supervisor->processPools->mapWithKeys(function ($pool) use ($supervisor) {
            return [$supervisor->options->connection.':'.$pool->queue() => count($pool->processes())];
        })->toJson();

        $this->connection()->hmset(
            'supervisor:'.$supervisor->name,
            'name', $supervisor->name,
            'master', explode(':', $supervisor->name)[0],
            'pid', $supervisor->pid(),
            'status', $supervisor->working ? 'running' : 'paused',
            'processes', $processes,
            'options', $supervisor->options->toJson()
        );

        $this->connection()->expire(
            'supervisor:'.$supervisor->name, 30
        );
    }

    /**
     * Remove the supervisor information from storage.
     *
     * @param  array|string  $name
     * @return void
     */
    public function forget($names)
    {
        $names = (array) $names;

        if (empty($names)) {
            return;
        }

        $this->connection()->del(...collect($names)->map(function ($name) {
            return 'supervisor:'.$name;
        })->all());
    }

    /**
     * Get the Redis connection instance.
     *
     * @return \Illuminate\Redis\Connetions\Connection
     */
    protected function connection()
    {
        return $this->redis->connection('horizon-supervisors');
    }
}
