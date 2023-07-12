<?php

namespace Laravel\Horizon\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\Arr;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\MasterSupervisor;

class RedisMasterSupervisorRepository implements MasterSupervisorRepository
{
    /**
     * The Redis connection instance.
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    public $redis;

    /**
     * Create a new repository instance.
     *
     * @param  \Illuminate\Contracts\Redis\Factory  $redis
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
        return $this->connection()->zrevrangebyscore('masters', '+inf',
            CarbonImmutable::now()->subSeconds(14)->getTimestamp()
        );
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
        $records = $this->connection()->pipeline(function ($pipe) use ($names) {
            foreach ($names as $name) {
                $pipe->hmget('master:'.$name, ['name', 'environment', 'pid', 'status', 'supervisors']);
            }
        });

        return collect($records)->map(function ($record) {
            $record = array_values($record);

            return ! $record[0] ? null : (object) [
                'name' => $record[0],
                'environment' => $record[1],
                'pid' => $record[2],
                'status' => $record[3],
                'supervisors' => json_decode($record[4], true),
            ];
        })->filter()->all();
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

        $this->connection()->pipeline(function ($pipe) use ($master, $supervisors) {
            $pipe->hmset(
                'master:'.$master->name, [
                    'name' => $master->name,
                    'environment' => $master->environment,
                    'pid' => $master->pid(),
                    'status' => $master->working ? 'running' : 'paused',
                    'supervisors' => json_encode($supervisors),
                ]
            );

            $pipe->zadd('masters',
                CarbonImmutable::now()->getTimestamp(), $master->name
            );

            $pipe->expire('master:'.$master->name, 15);
        });
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

        $this->connection()->del('master:'.$name);

        $this->connection()->zrem('masters', $name);
    }

    /**
     * Remove expired master supervisors from storage.
     *
     * @return void
     */
    public function flushExpired()
    {
        $this->connection()->zremrangebyscore('masters', '-inf',
            CarbonImmutable::now()->subSeconds(14)->getTimestamp()
        );
    }

    /**
     * Get the Redis connection instance.
     *
     * @return \Illuminate\Redis\Connections\Connection
     */
    protected function connection()
    {
        return $this->redis->connection('horizon');
    }
}
