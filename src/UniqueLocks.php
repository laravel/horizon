<?php

namespace Laravel\Horizon;

use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Support\Str;

class UniqueLocks
{
    /**
     * The prefix of the cache keys used for unique job locks.
     */
    const PREFIX = 'laravel_unique_job:';

    /**
     * The maximum number of locks to return.
     *
     * @var int
     */
    public $limit = 1000;

    /**
     * Create a new unique locks instance.
     *
     * @param  \Illuminate\Contracts\Cache\Factory  $cache
     * @return void
     */
    public function __construct(public CacheFactory $cache)
    {
        //
    }

    /**
     * Determine if the default cache store's locks can be listed.
     *
     * @return bool
     */
    public function supported()
    {
        $store = $this->store();

        return $store instanceof RedisStore || $store instanceof DatabaseStore;
    }

    /**
     * Get all of the unique job locks.
     *
     * @return \Illuminate\Support\Collection<int, array{key: string, name: string, unique_id: string, owner: string|null, expires_in: int|null}>
     */
    public function all()
    {
        $store = $this->store();

        $locks = match (true) {
            $store instanceof RedisStore => $this->fromRedis($store),
            $store instanceof DatabaseStore => $this->fromDatabase($store),
            default => collect(),
        };

        return $locks->map(function ($lock) {
            [, $name, $uniqueId] = array_pad(explode(':', $lock['key'], 3), 3, '');

            return ['key' => $lock['key'], 'name' => $name, 'unique_id' => $uniqueId] + $lock;
        })->sortBy('key')->values();
    }

    /**
     * Release the given unique job lock.
     *
     * @param  string  $key
     * @return void
     */
    public function release($key)
    {
        $this->cache->store()->lock($key)->forceRelease();
    }

    /**
     * Get the unique job locks from a Redis cache store.
     *
     * @param  \Illuminate\Cache\RedisStore  $store
     * @return \Illuminate\Support\Collection
     */
    protected function fromRedis(RedisStore $store)
    {
        $connection = $store->lockConnection();

        $connectionPrefix = match (true) {
            $connection instanceof PhpRedisConnection => $connection->_prefix(''),
            $connection instanceof PredisConnection => $connection->getOptions()->prefix ?: '',
            default => '',
        };

        $prefix = $store->getPrefix().static::PREFIX;

        $match = $connection instanceof PhpRedisConnection &&
            defined('Redis::SCAN_PREFIX') &&
            $connection->client()->getOption(\Redis::OPT_SCAN) === \Redis::SCAN_PREFIX
                ? $prefix.'*'
                : $connectionPrefix.$prefix.'*';

        $defaultCursor = $connection instanceof PhpRedisConnection && version_compare(phpversion('redis'), '6.1.0', '>=')
            ? null
            : '0';

        $cursor = $defaultCursor;
        $keys = [];

        do {
            $result = $connection->scan($cursor, ['match' => $match, 'count' => 1000]);

            if (! is_array($result)) {
                break;
            }

            [$cursor, $chunk] = $result;

            foreach ((array) $chunk as $key) {
                $keys[] = Str::after($key, $connectionPrefix.$store->getPrefix());
            }
        } while (((string) $cursor) !== ((string) $defaultCursor) && count($keys) < $this->limit);

        $keys = array_slice(array_values(array_unique($keys)), 0, $this->limit);

        $results = $connection->pipeline(function ($pipe) use ($keys, $store) {
            foreach ($keys as $key) {
                $pipe->get($store->getPrefix().$key);
                $pipe->ttl($store->getPrefix().$key);
            }
        });

        return collect($keys)->values()->map(fn ($key, $i) => [
            'key' => $key,
            'owner' => is_string($results[$i * 2] ?? null) ? $results[$i * 2] : null,
            'expires_in' => ($ttl = (int) ($results[$i * 2 + 1] ?? -1)) >= 0 ? $ttl : null,
        ]);
    }

    /**
     * Get the unique job locks from a database cache store.
     *
     * @param  \Illuminate\Cache\DatabaseStore  $store
     * @return \Illuminate\Support\Collection
     */
    protected function fromDatabase(DatabaseStore $store)
    {
        $config = config('cache.stores.'.config('cache.default'));

        $prefix = $store->getPrefix().static::PREFIX;

        return app('db')
            ->connection($config['lock_connection'] ?? $config['connection'] ?? null)
            ->table($config['lock_table'] ?? 'cache_locks')
            ->where('key', 'like', $prefix.'%')
            ->where('expiration', '>', now()->getTimestamp())
            ->limit($this->limit)
            ->get()
            ->filter(fn ($lock) => str_starts_with($lock->key, $prefix))
            ->map(fn ($lock) => [
                'key' => Str::after($lock->key, $store->getPrefix()),
                'owner' => $lock->owner,
                'expires_in' => max(0, $lock->expiration - now()->getTimestamp()),
            ]);
    }

    /**
     * Get the default cache store.
     *
     * @return \Illuminate\Contracts\Cache\Store
     */
    protected function store()
    {
        return $this->cache->store()->getStore();
    }
}
