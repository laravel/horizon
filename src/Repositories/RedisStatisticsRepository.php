<?php

namespace Laravel\Horizon\Repositories;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;
use Laravel\Horizon\Contracts\StatisticsRepository;

class RedisStatisticsRepository implements StatisticsRepository
{
    /**
     * @var RedisFactory
     */
    public $redis;

    public function __construct(RedisFactory $redis)
    {
        $this->redis = $redis;
    }

    public function byType(string $type): array
    {
        $prefix = config('horizon.prefix');
        $prefixIndex = config('horizon.prefix_index');

        $keys = $this->connection()->keys("{$type}_jobs:{$prefixIndex}*");

        return collect($keys)
            ->map(function ($key) use ($prefix, $prefixIndex) {

                $class = preg_match("/{$prefixIndex}(.*)$/", $key, $matches) ? $matches[1] : $key;

                $keyForCount = preg_match("/{$prefix}(.*)$/", $key, $matches) ? $matches[1] : $key;

                return [
                    'class' => $class,
                    'count' => $this->connection()->zcount($keyForCount, '-inf', '+inf'),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->toArray();
    }

    protected function connection(): Connection
    {
        return $this->redis->connection('horizon');
    }
}
