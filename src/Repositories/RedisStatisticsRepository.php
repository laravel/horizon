<?php

namespace Laravel\Horizon\Repositories;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Str;
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
            ->map(function (string $indexKey) use ($prefix, $prefixIndex) {

                $class = Str::after($indexKey, $prefixIndex);
                $keyForCount = Str::after($indexKey, $prefix);

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
