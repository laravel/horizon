<?php

namespace Laravel\Horizon;

use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionResolverInterface;

class DatabaseLock extends Lock
{
    /**
     * The database connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    public $resolver;

    /**
     * Create a Horizon database lock manager.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @return void
     */
    public function __construct(ConnectionResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Determine if a lock exists for the given key.
     *
     * @param  string  $key
     * @return bool
     */
    public function exists($key)
    {
        return $this->table()
            ->where('key', $key)
            ->where('expires_at', '>', CarbonImmutable::now()->getTimestamp())
            ->exists();
    }

    /**
     * Attempt to get a lock for the given key.
     *
     * @param  string  $key
     * @param  int  $seconds
     * @return bool
     */
    public function get($key, $seconds = 60)
    {
        $this->prune();

        return $this->table()->insertOrIgnore([
            'key' => $key,
            'expires_at' => CarbonImmutable::now()->addSeconds($seconds)->getTimestamp(),
        ]) === 1;
    }

    /**
     * Release the lock for the given key.
     *
     * @param  string  $key
     * @return void
     */
    public function release($key)
    {
        $this->table()->where('key', $key)->delete();
    }

    /**
     * Remove any expired lock records.
     *
     * @return void
     */
    protected function prune()
    {
        $this->table()
            ->where('expires_at', '<=', CarbonImmutable::now()->getTimestamp())
            ->delete();
    }

    /**
     * Get a query builder for the locks table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        return $this->resolver->connection(
            config('horizon.database.connection')
        )->table('horizon_locks');
    }
}
