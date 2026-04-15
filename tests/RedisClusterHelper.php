<?php

namespace Laravel\Horizon\Tests;

use Illuminate\Contracts\Foundation\Application;
use Laravel\Horizon\Horizon;

class RedisClusterHelper
{
    /**
     * Configure the application for Redis Cluster when the
     * REDIS_CLUSTER_HOSTS_AND_PORTS environment variable is set.
     */
    public static function configure(Application $app): void
    {
        if (! $hosts = getenv('REDIS_CLUSTER_HOSTS_AND_PORTS')) {
            return;
        }

        $client = getenv('REDIS_CLIENT') ?: 'phpredis';

        $nodes = array_map(
            static fn ($hostAndPort) => [
                'host' => explode(':', $hostAndPort)[0],
                'port' => explode(':', $hostAndPort)[1],
            ],
            explode(',', $hosts),
        );

        $app->make('config')->set('database.redis.clusters.default', $nodes);

        Horizon::use(config('horizon.use', 'default'));

        // During bootstrap, HorizonServiceProvider::configure() calls Horizon::use()
        // before cluster config exists, so it creates a standalone "database.redis.horizon"
        // connection. RedisManager::resolve() checks standalone keys before cluster keys,
        // so "default" and "horizon" would resolve to standalone instead of cluster.
        // Replace the entire config to remove all standalone connections.
        $app->make('config')->set('database.redis', [
            'client' => $client,
            'options' => [
                'cluster' => 'redis',
                'prefix' => '',
            ],
            'clusters' => [
                'default' => $nodes,
                'horizon' => $app->make('config')->get('database.redis.clusters.horizon'),
            ],
        ]);
    }
}
