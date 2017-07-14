<?php

namespace Laravel\Horizon\Repositories;

use Illuminate\Support\Arr;
use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;

class RedisMasterSupervisorRepository implements MasterSupervisorRepository
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
     * Get the names of all the master supervisors currently running.
     *
     * @return array
     */
    public function names()
    {
        return collect($this->connection()->keys('master:*'))->map(function ($name) {
            return substr($name, 7);
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
     * Get information on a master supervisor by name.
     *
     * @param  string  $name
     * @return \StdClass|null
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
        $records = $this->connection()->pipeline(function ($pipe) use ($names) {
            foreach ($names as $name) {
                $pipe->hmget('master:'.$name, 'name', 'pid', 'status', 'supervisors');
            }
        });

        return collect($records)->map(function ($record) {
            return is_null($record[0]) ? null : (object) [
                'name' => $record[0],
                'pid' => $record[1],
                'status' => $record[2],
                'supervisors' => json_decode($record[3], true),
            ];
        })->filter()->all();
    }

    /**
     * Update the information about the given master supervisor.
     *
     * @param  MasterSupervisor  $master
     * @return void
     */
    public function update(MasterSupervisor $master)
    {
        $supervisors = $master->supervisors->map->name->all();

        $this->connection()->hmset(
            'master:'.$master->name,
            'name', $master->name,
            'pid', $master->pid(),
            'status', $master->working ? 'running' : 'paused',
            'supervisors', json_encode($supervisors)
        );

        $this->connection()->expire(
            'master:'.$master->name, 15
        );
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

        resolve(SupervisorRepository::class)->forget(
            $master->supervisors
        );

        $this->connection()->del('master:'.$name);
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
