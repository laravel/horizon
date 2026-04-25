<?php

namespace Laravel\Horizon\Tests\Database;

use Laravel\Horizon\Lock;

class DatabaseLockTest extends DatabaseTestCase
{
    public function test_lock_can_be_acquired_and_released()
    {
        $lock = resolve(Lock::class);

        $this->assertTrue($lock->get('foo'));
        $this->assertTrue($lock->exists('foo'));
        $this->assertFalse($lock->get('foo'));

        $lock->release('foo');

        $this->assertFalse($lock->exists('foo'));
        $this->assertTrue($lock->get('foo'));
    }

    public function test_expired_locks_are_pruned()
    {
        $lock = resolve(Lock::class);

        $this->assertTrue($lock->get('foo', -1));
        $this->assertFalse($lock->exists('foo'));
        $this->assertTrue($lock->get('foo'));
    }

    public function test_with_executes_callback_when_lock_acquired()
    {
        $lock = resolve(Lock::class);
        $executed = false;

        $lock->with('foo', function () use (&$executed) {
            $executed = true;
        });

        $this->assertTrue($executed);
        $this->assertFalse($lock->exists('foo'));
    }

    public function test_with_does_not_execute_callback_when_lock_unavailable()
    {
        $lock = resolve(Lock::class);
        $executed = false;

        $lock->get('foo');

        $lock->with('foo', function () use (&$executed) {
            $executed = true;
        });

        $this->assertFalse($executed);
    }
}
