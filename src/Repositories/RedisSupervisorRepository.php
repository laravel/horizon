<?php

namespace Laravel\Horizon\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\Arr;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Supervisor;

class RedisSupervisorRepository implements SupervisorRepository
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
     * Get the names of all the supervisors currently running.
     *
     * @return array
     */
    public function names()
    {
        return $this->connection()->zrevrangebyscore('supervisors', '+inf',
            CarbonImmutable::now()->subSeconds(29)->getTimestamp()
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
        $records = $this->connection()->pipeline(function ($pipe) use ($names) {
            foreach ($names as $name) {
                $pipe->hmget('supervisor:'.$name, ['name', 'master', 'pid', 'status', 'processes', 'options']);
            }
        });

        return collect($records)
            ->filter()
            ->map(function ($record) {
                $record = array_values($record);

                return ! $record[0] ? null : (object) [
                    'name' => $record[0],
                    'master' => $record[1],
                    'pid' => $record[2],
                    'status' => $record[3],
                    'processes' => json_decode($record[4], true),
                    'options' => json_decode($record[5], true),
                ];
            })
            ->filter()
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
            ->max(fn ($supervisor) => $supervisor->options['timeout']) ?: 0;
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
            ->toJson();

        $this->connection()->pipeline(function ($pipe) use ($supervisor, $processes) {
            $pipe->hmset(
                'supervisor:'.$supervisor->name, [
                    'name' => $supervisor->name,
                    'master' => implode(':', explode(':', $supervisor->name, -1)),
                    'pid' => $supervisor->pid(),
                    'status' => $supervisor->working ? 'running' : 'paused',
                    'processes' => $processes,
                    'options' => $supervisor->options->toJson(),
                ]
            );

            $pipe->zadd('supervisors',
                CarbonImmutable::now()->getTimestamp(), $supervisor->name
            );

            $pipe->expire('supervisor:'.$supervisor->name, 30);
        });
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

        $this->connection()->del(...collect($names)->map(function ($name) {
            return 'supervisor:'.$name;
        })->all());

        $this->connection()->zrem('supervisors', ...$names);
    }

    /**
     * Remove expired supervisors from storage.
     *
     * @return void
     */
    public function flushExpired()
    {
        $this->connection()->zremrangebyscore('supervisors', '-inf',
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
