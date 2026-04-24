<?php

namespace Laravel\Horizon\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;
use Laravel\Horizon\DatabaseLock;
use Laravel\Horizon\Lock;
use Laravel\Horizon\Tests\DatabaseIntegrationTest;

class DatabaseLockTest extends DatabaseIntegrationTest
{
    protected function lock(): DatabaseLock
    {
        return $this->app->make(Lock::class);
    }

    public function test_binding_resolves_to_database_lock()
    {
        $this->assertInstanceOf(DatabaseLock::class, $this->lock());
    }

    public function test_first_acquisition_succeeds()
    {
        $this->assertTrue($this->lock()->get('some-key'));
    }

    public function test_second_acquisition_fails_before_release()
    {
        $lock = $this->lock();

        $this->assertTrue($lock->get('contended-key'));
        $this->assertFalse($lock->get('contended-key'));
    }

    public function test_release_allows_reacquisition()
    {
        $lock = $this->lock();

        $lock->get('release-key');
        $lock->release('release-key');

        $this->assertTrue($lock->get('release-key'));
    }

    public function test_expired_lock_can_be_reacquired()
    {
        $lock = $this->lock();

        CarbonImmutable::setTestNow(CarbonImmutable::now());

        $this->assertTrue($lock->get('ttl-key', 10));

        CarbonImmutable::setTestNow(CarbonImmutable::now()->addSeconds(20));

        $this->assertTrue($lock->get('ttl-key', 10));

        CarbonImmutable::setTestNow();
    }

    public function test_exists_reflects_current_state()
    {
        $lock = $this->lock();

        $this->assertFalse($lock->exists('check-key'));

        $lock->get('check-key', 60);

        $this->assertTrue($lock->exists('check-key'));

        $lock->release('check-key');

        $this->assertFalse($lock->exists('check-key'));
    }

    public function test_horizon_locks_table_has_no_owner_column()
    {
        $this->assertFalse(
            Schema::hasColumn('horizon_locks', 'owner'),
            'horizon_locks.owner was removed in favor of RedisLock-style fire-and-forget semantics.'
        );
    }

    public function test_release_for_non_existent_key_is_noop()
    {
        $this->lock()->release('never-acquired-key');

        $this->assertFalse($this->lock()->exists('never-acquired-key'));
    }

    public function test_with_runs_callback_when_lock_is_acquired()
    {
        $lock = $this->lock();

        $ran = false;
        $lock->with('with-key', function () use (&$ran) {
            $ran = true;
        });

        $this->assertTrue($ran);
        $this->assertFalse($lock->exists('with-key'));
    }

    public function test_get_does_not_wrap_in_a_transaction()
    {
        $lock = $this->lock();

        $levels = [];

        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$levels) {
            $sql = strtolower(trim($query->sql));

            if (str_contains($sql, 'horizon_locks')) {
                $levels[] = \Illuminate\Support\Facades\DB::transactionLevel();
            }
        });

        $lock->get('no-txn-key');

        $this->assertNotEmpty($levels);
        foreach ($levels as $level) {
            $this->assertSame(0, $level, 'Lock acquisition must run outside a transaction.');
        }
    }
}
