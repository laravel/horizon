<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Lock;
use Laravel\Horizon\Tests\IntegrationTest;

class LockTest extends IntegrationTest
{
    public function test_lock_sets_ttl_atomically()
    {
        $lock = new Lock(app('redis'));

        $lock->get('horizon:test:atomic', 60);

        $ttl = Redis::connection('horizon')->ttl('horizon:test:atomic');

        $this->assertGreaterThan(0, $ttl, 'Lock key should have a positive TTL');
        $this->assertLessThanOrEqual(60, $ttl, 'Lock TTL should not exceed the requested seconds');

        $lock->release('horizon:test:atomic');
    }

    public function test_lock_cannot_be_acquired_twice()
    {
        $lock = new Lock(app('redis'));

        $this->assertTrue($lock->get('horizon:test:double', 60));
        $this->assertFalse($lock->get('horizon:test:double', 60));

        $lock->release('horizon:test:double');
    }

    public function test_lock_auto_expires_after_ttl()
    {
        $lock = new Lock(app('redis'));

        // Acquire with a 1-second TTL
        $this->assertTrue($lock->get('horizon:test:expiry', 1));

        // Lock should exist
        $this->assertTrue($lock->exists('horizon:test:expiry'));

        // Wait for expiry
        sleep(2);

        // Lock should have self-released
        $this->assertFalse($lock->exists('horizon:test:expiry'));

        // Should be acquirable again
        $this->assertTrue($lock->get('horizon:test:expiry', 1));

        $lock->release('horizon:test:expiry');
    }

    public function test_lock_recovers_after_simulated_crash()
    {
        $lock = new Lock(app('redis'));

        // Acquire lock with 2-second TTL
        $this->assertTrue($lock->get('horizon:test:crash', 2));

        // Simulate crash: do NOT call release()
        // With the old SETNX+EXPIRE bug, if crash happened between the two
        // commands, the key would have TTL -1 (permanent).
        // With the atomic SET NX EX fix, TTL is always set.

        // Verify TTL is positive (not -1)
        $ttl = Redis::connection('horizon')->ttl('horizon:test:crash');
        $this->assertGreaterThan(0, $ttl, 'Lock must have a positive TTL even without explicit release');

        // Wait for auto-expiry
        sleep(3);

        // Lock should have self-healed — acquirable again without manual DEL
        $this->assertTrue(
            $lock->get('horizon:test:crash', 2),
            'Lock should be acquirable after TTL expires (self-healing after crash)'
        );

        $lock->release('horizon:test:crash');
    }

    public function test_with_releases_lock_after_callback()
    {
        $lock = new Lock(app('redis'));
        $executed = false;

        $lock->with('horizon:test:with', function () use (&$executed) {
            $executed = true;
        }, 60);

        $this->assertTrue($executed);
        $this->assertFalse($lock->exists('horizon:test:with'));
    }

    public function test_with_releases_lock_even_on_exception()
    {
        $lock = new Lock(app('redis'));

        try {
            $lock->with('horizon:test:exception', function () {
                throw new \RuntimeException('test');
            }, 60);
        } catch (\RuntimeException $e) {
            // expected
        }

        $this->assertFalse(
            $lock->exists('horizon:test:exception'),
            'Lock should be released even when callback throws'
        );
    }
}
