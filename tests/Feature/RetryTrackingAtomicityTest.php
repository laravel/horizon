<?php

namespace Laravel\Horizon\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\LuaScripts;
use Laravel\Horizon\Tests\IntegrationTest;

class RetryTrackingAtomicityTest extends IntegrationTest
{
    /**
     * Original PHP implementation of updateRetryStatus (pre-Lua).
     */
    private function phpUpdateRetryStatus(array $retries, string $jobId, bool $failed): array
    {
        return collect($retries)
            ->map(function ($retry) use ($jobId, $failed) {
                return $retry['id'] === $jobId
                    ? Arr::set($retry, 'status', $failed ? 'failed' : 'completed')
                    : $retry;
            })
            ->all();
    }

    /**
     * Original PHP implementation of storeRetryReference (pre-Lua).
     */
    private function phpStoreRetryReference(array $retries, string $retryId, int $timestamp): array
    {
        $retries[] = [
            'id' => $retryId,
            'status' => 'pending',
            'retried_at' => $timestamp,
        ];

        return $retries;
    }

    /**
     * Run the Lua updateRetryStatus and return the decoded result.
     */
    private function luaUpdateRetryStatus(string $key, array $retries, string $jobId, bool $failed): ?array
    {
        $conn = Redis::connection('horizon');
        $conn->hset($key, 'retried_by', json_encode($retries));

        $conn->eval(LuaScripts::updateRetryStatus(), 1, $key, $jobId, $failed ? 'failed' : 'completed');

        $raw = $conn->hget($key, 'retried_by');

        return $raw ? json_decode($raw, true) : null;
    }

    /**
     * Run the Lua storeRetryReference and return the decoded result.
     */
    private function luaStoreRetryReference(string $key, array $retries, string $retryId, int $timestamp): array
    {
        $conn = Redis::connection('horizon');

        if (! empty($retries)) {
            $conn->hset($key, 'retried_by', json_encode($retries));
        } else {
            $conn->del($key);
        }

        $conn->eval(LuaScripts::storeRetryReference(), 1, $key, $retryId, $timestamp);

        return json_decode($conn->hget($key, 'retried_by'), true);
    }
    public function test_update_retry_status_updates_correct_entry()
    {
        $conn = Redis::connection('horizon');
        $key = 'test:retry:update';

        $retries = [
            ['id' => 'job-aaa', 'status' => 'pending', 'retried_at' => 1000],
            ['id' => 'job-bbb', 'status' => 'pending', 'retried_at' => 1001],
        ];
        $conn->hset($key, 'retried_by', json_encode($retries));

        $conn->eval(LuaScripts::updateRetryStatus(), 1, $key, 'job-aaa', 'completed');

        $result = json_decode($conn->hget($key, 'retried_by'), true);

        $this->assertEquals('completed', $result[0]['status']);
        $this->assertEquals('pending', $result[1]['status']);
    }

    public function test_update_retry_status_does_nothing_when_key_missing()
    {
        $conn = Redis::connection('horizon');
        $key = 'test:retry:missing';
        $conn->del($key);

        // Should not throw
        $conn->eval(LuaScripts::updateRetryStatus(), 1, $key, 'job-xxx', 'failed');

        $this->assertNull($conn->hget($key, 'retried_by'));
    }

    public function test_store_retry_reference_appends_to_existing()
    {
        $conn = Redis::connection('horizon');
        $key = 'test:retry:append';

        $retries = [
            ['id' => 'job-aaa', 'status' => 'completed', 'retried_at' => 1000],
        ];
        $conn->hset($key, 'retried_by', json_encode($retries));

        $conn->eval(LuaScripts::storeRetryReference(), 1, $key, 'job-bbb', 2000);

        $result = json_decode($conn->hget($key, 'retried_by'), true);

        $this->assertCount(2, $result);
        $this->assertEquals('job-aaa', $result[0]['id']);
        $this->assertEquals('job-bbb', $result[1]['id']);
        $this->assertEquals('pending', $result[1]['status']);
        $this->assertEquals(2000, $result[1]['retried_at']);
    }

    public function test_store_retry_reference_creates_array_when_empty()
    {
        $conn = Redis::connection('horizon');
        $key = 'test:retry:empty';
        $conn->del($key);

        $conn->eval(LuaScripts::storeRetryReference(), 1, $key, 'job-first', 3000);

        $result = json_decode($conn->hget($key, 'retried_by'), true);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('job-first', $result[0]['id']);
        $this->assertEquals('pending', $result[0]['status']);
    }

    public function test_lua_json_is_compatible_with_php_json_decode()
    {
        $conn = Redis::connection('horizon');
        $key = 'test:retry:compat';

        // Store via PHP (as current code does)
        $retries = [
            ['id' => 'job-1', 'status' => 'pending', 'retried_at' => 1711234567],
        ];
        $conn->hset($key, 'retried_by', json_encode($retries));

        // Update via Lua
        $conn->eval(LuaScripts::updateRetryStatus(), 1, $key, 'job-1', 'completed');

        // Read via PHP (as dashboard does)
        $result = json_decode($conn->hget($key, 'retried_by'), true);

        $this->assertIsArray($result);
        $this->assertTrue(array_is_list($result));
        $this->assertEquals('completed', $result[0]['status']);
        $this->assertEquals('job-1', $result[0]['id']);
        $this->assertEquals(1711234567, $result[0]['retried_at']);
    }

    // ---------------------------------------------------------------
    //  Parity tests: PHP (original) vs Lua (new) produce same results
    // ---------------------------------------------------------------

    public function test_parity_update_status_marks_completed()
    {
        $retries = [
            ['id' => 'job-aaa', 'status' => 'pending', 'retried_at' => 1000],
            ['id' => 'job-bbb', 'status' => 'pending', 'retried_at' => 1001],
            ['id' => 'job-ccc', 'status' => 'pending', 'retried_at' => 1002],
        ];

        $phpResult = $this->phpUpdateRetryStatus($retries, 'job-bbb', false);
        $luaResult = $this->luaUpdateRetryStatus('test:parity:completed', $retries, 'job-bbb', false);

        $this->assertSame($phpResult, $luaResult);
    }

    public function test_parity_update_status_marks_failed()
    {
        $retries = [
            ['id' => 'job-aaa', 'status' => 'pending', 'retried_at' => 1000],
            ['id' => 'job-bbb', 'status' => 'pending', 'retried_at' => 1001],
        ];

        $phpResult = $this->phpUpdateRetryStatus($retries, 'job-aaa', true);
        $luaResult = $this->luaUpdateRetryStatus('test:parity:failed', $retries, 'job-aaa', true);

        $this->assertSame($phpResult, $luaResult);
    }

    public function test_parity_update_status_no_matching_id()
    {
        $retries = [
            ['id' => 'job-aaa', 'status' => 'pending', 'retried_at' => 1000],
        ];

        $phpResult = $this->phpUpdateRetryStatus($retries, 'job-nonexistent', false);
        $luaResult = $this->luaUpdateRetryStatus('test:parity:nomatch', $retries, 'job-nonexistent', false);

        $this->assertSame($phpResult, $luaResult);
    }

    public function test_parity_store_reference_appends_to_existing()
    {
        $retries = [
            ['id' => 'job-aaa', 'status' => 'completed', 'retried_at' => 1000],
        ];
        $timestamp = 2000;

        $phpResult = $this->phpStoreRetryReference($retries, 'job-bbb', $timestamp);
        $luaResult = $this->luaStoreRetryReference('test:parity:append', $retries, 'job-bbb', $timestamp);

        $this->assertSame($phpResult, $luaResult);
    }

    public function test_parity_store_reference_creates_from_empty()
    {
        $timestamp = 3000;

        $phpResult = $this->phpStoreRetryReference([], 'job-first', $timestamp);
        $luaResult = $this->luaStoreRetryReference('test:parity:empty', [], 'job-first', $timestamp);

        $this->assertSame($phpResult, $luaResult);
    }

    public function test_parity_store_multiple_sequential_references()
    {
        $conn = Redis::connection('horizon');
        $key = 'test:parity:multi';
        $conn->del($key);

        $phpRetries = [];
        $ids = ['job-1', 'job-2', 'job-3'];
        $timestamps = [1000, 2000, 3000];

        // Build up via PHP
        foreach ($ids as $i => $id) {
            $phpRetries = $this->phpStoreRetryReference($phpRetries, $id, $timestamps[$i]);
        }

        // Build up via Lua
        foreach ($ids as $i => $id) {
            $conn->eval(LuaScripts::storeRetryReference(), 1, $key, $id, $timestamps[$i]);
        }

        $luaRetries = json_decode($conn->hget($key, 'retried_by'), true);

        $this->assertSame($phpRetries, $luaRetries);
    }

    public function test_parity_update_after_store_round_trip()
    {
        $conn = Redis::connection('horizon');
        $key = 'test:parity:roundtrip';
        $conn->del($key);

        $timestamp = 1711234567;

        // PHP round-trip: store then update
        $phpRetries = $this->phpStoreRetryReference([], 'job-abc', $timestamp);
        $phpRetries = $this->phpUpdateRetryStatus($phpRetries, 'job-abc', false);

        // Lua round-trip: store then update
        $conn->eval(LuaScripts::storeRetryReference(), 1, $key, 'job-abc', $timestamp);
        $conn->eval(LuaScripts::updateRetryStatus(), 1, $key, 'job-abc', 'completed');

        $luaRetries = json_decode($conn->hget($key, 'retried_by'), true);

        $this->assertSame($phpRetries, $luaRetries);
    }
}
