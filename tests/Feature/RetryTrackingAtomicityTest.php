<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\LuaScripts;
use Laravel\Horizon\Tests\IntegrationTest;

class RetryTrackingAtomicityTest extends IntegrationTest
{
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
}
