<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Tests\IntegrationTest;

/**
 * This test demonstrates the non-atomic SETNX+EXPIRE bug.
 *
 * The old Lock::get() uses two separate Redis commands:
 *   1. SETNX key 1
 *   2. EXPIRE key 60
 *
 * If a crash occurs between step 1 and step 2, the key exists
 * with TTL -1 (no expiry) — a permanent deadlock.
 *
 * The fix uses SET key 1 EX 60 NX — a single atomic command
 * where the key and TTL are set together. No crash window.
 */
class LockAtomicityTest extends IntegrationTest
{
    /**
     * Simulate the OLD buggy behavior: SETNX without EXPIRE.
     * This is what happens when a process crashes between the two commands.
     */
    public function test_setnx_without_expire_creates_permanent_lock()
    {
        $conn = Redis::connection('horizon');

        // Step 1: SETNX succeeds (simulating old Lock::get() step 1)
        $result = $conn->setnx('horizon:bug:demo', 1);
        $this->assertEquals(1, $result, 'SETNX should succeed');

        // Step 2: EXPIRE never runs (simulating crash)
        // $conn->expire('horizon:bug:demo', 60);  // CRASHED HERE

        // The key now has TTL -1 — it will NEVER expire
        $ttl = $conn->ttl('horizon:bug:demo');
        $this->assertEquals(-1, $ttl, 'Key without EXPIRE has TTL -1 (permanent)');

        // Another process tries to acquire the same lock — blocked forever
        $result2 = $conn->setnx('horizon:bug:demo', 1);
        $this->assertEquals(0, $result2, 'Second SETNX should fail — lock is held');

        // Even after waiting, it's still locked (using a short sleep to demonstrate)
        usleep(500000); // 500ms
        $ttl2 = $conn->ttl('horizon:bug:demo');
        $this->assertEquals(-1, $ttl2, 'TTL is still -1 — lock will never self-heal');

        // Only manual intervention can fix it
        $conn->del('horizon:bug:demo');
    }

    /**
     * The FIX: SET NX EX is atomic — TTL is always set, even if crash follows.
     */
    public function test_set_nx_ex_always_has_ttl()
    {
        $conn = Redis::connection('horizon');

        // Atomic: key + TTL set in one command
        $result = $conn->set('horizon:fix:demo', 1, 'EX', 2, 'NX');
        $this->assertTrue($result == true, 'SET NX EX should succeed');

        // SIMULATE CRASH HERE — but it doesn't matter, TTL is already set
        // The key has a positive TTL, guaranteed
        $ttl = $conn->ttl('horizon:fix:demo');
        $this->assertGreaterThan(0, $ttl, 'Key always has positive TTL with SET NX EX');
        $this->assertLessThanOrEqual(2, $ttl);

        // Another process can't acquire yet
        $result2 = $conn->set('horizon:fix:demo', 1, 'EX', 2, 'NX');
        $this->assertFalse($result2 == true, 'Second SET NX EX should fail while lock held');

        // Wait for TTL to expire (self-healing)
        sleep(3);

        // Lock has auto-released — acquirable again WITHOUT manual DEL
        $result3 = $conn->set('horizon:fix:demo', 1, 'EX', 2, 'NX');
        $this->assertTrue($result3 == true, 'Lock should self-heal after TTL expires');

        $conn->del('horizon:fix:demo');
    }

    /**
     * Direct comparison: old vs new under simulated crash conditions.
     */
    public function test_crash_recovery_comparison()
    {
        $conn = Redis::connection('horizon');

        // === OLD WAY: SETNX + crash before EXPIRE ===
        $conn->setnx('horizon:old', 1);
        // crash — no expire
        $oldTtl = $conn->ttl('horizon:old');

        // === NEW WAY: SET NX EX + crash after ===
        $conn->set('horizon:new', 1, 'EX', 5, 'NX');
        // crash — doesn't matter
        $newTtl = $conn->ttl('horizon:new');

        // The difference
        $this->assertEquals(-1, $oldTtl, 'OLD: TTL is -1 (permanent deadlock)');
        $this->assertGreaterThan(0, $newTtl, 'NEW: TTL is positive (will self-heal)');

        echo "\n";
        echo "  OLD (SETNX, no EXPIRE): TTL = {$oldTtl} (PERMANENT DEADLOCK)\n";
        echo "  NEW (SET NX EX):        TTL = {$newTtl}s (self-heals)\n";

        // Cleanup
        $conn->del('horizon:old');
        $conn->del('horizon:new');
    }
}
