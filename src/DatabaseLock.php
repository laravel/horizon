<?php

namespace Laravel\Horizon;

use Carbon\CarbonImmutable;
use Laravel\Horizon\Models\HorizonLock;

class DatabaseLock extends Lock
{
    /**
     * Determine if a lock exists for the given key.
     *
     * @param  string  $key
     * @return bool
     */
    #[\Override]
    public function exists($key)
    {
        return HorizonLock::where('key', $key)
            ->where('expires_at', '>', CarbonImmutable::now())
            ->exists();
    }

    /**
     * Attempt to get a lock for the given key.
     *
     * @param  string  $key
     * @param  int  $seconds
     * @return bool
     */
    #[\Override]
    public function get($key, $seconds = 60)
    {
        $now = CarbonImmutable::now();

        HorizonLock::where('key', $key)
            ->where('expires_at', '<=', $now)
            ->delete();

        $inserted = HorizonLock::query()->insertOrIgnore([
            'key' => $key,
            'expires_at' => $now->addSeconds($seconds),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $inserted === 1;
    }

    /**
     * Release the lock for the given key.
     *
     * @param  string  $key
     * @return void
     */
    #[\Override]
    public function release($key)
    {
        HorizonLock::where('key', $key)->delete();
    }
}
