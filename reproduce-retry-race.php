#!/usr/bin/env php
<?php

/**
 * Reproduction: Horizon retry tracking race condition (laravel/horizon#1744)
 *
 * This script demonstrates that RedisJobRepository::storeRetryReference()
 * silently loses retry entries when multiple workers retry jobs from the
 * same parent concurrently, due to non-atomic HGET -> modify in PHP -> HSET.
 *
 * The scenario simulated: a user clicks "Retry All" on a batch of failed jobs
 * that share the same parent. Multiple Horizon workers pick up the retries
 * simultaneously and each calls storeRetryReference() on the same parent key.
 *
 * Requirements: PHP 8.0+ with phpredis extension, a running Redis server.
 *
 * Usage:
 *   php reproduce-retry-race.php [workers] [retries-per-worker] [runs]
 *
 *   Defaults: 5 workers, 3 retries each = 15 expected, repeated 5 times.
 *
 * Environment:
 *   REDIS_HOST (default: 127.0.0.1)
 *   REDIS_PORT (default: 6379)
 *
 * Source comparison:
 *   The helper functions below replicate the logic from Horizon 5.x
 *   (commit daab248..HEAD on the 5.x branch). Minor API differences are
 *   noted in comments but do not affect the race condition pattern.
 */

// ---------------------------------------------------------------------------
// Config
// ---------------------------------------------------------------------------

$workers          = (int) ($argv[1] ?? 5);
$retriesPerWorker = (int) ($argv[2] ?? 3);
$runs             = (int) ($argv[3] ?? 5);
$expectedTotal    = $workers * $retriesPerWorker;
$redisHost        = getenv('REDIS_HOST') ?: '127.0.0.1';
$redisPort        = (int) (getenv('REDIS_PORT') ?: 6379);

// When running as a child, the parent key is passed via argv
$parentJobKey = $argv[6] ?? null;
$isChild      = isset($argv[4]) && $argv[4] === '--child';

// ---------------------------------------------------------------------------
// Helpers — replicate Horizon's non-atomic Redis patterns
// ---------------------------------------------------------------------------

/**
 * Replicates RedisJobRepository::storeRetryReference()
 *
 * Horizon source (5.x branch, src/Repositories/RedisJobRepository.php ~L700):
 *
 *   $retries = json_decode($this->connection()->hget($id, 'retried_by') ?: '[]');
 *   $retries[] = ['id' => $retryId, 'status' => 'pending',
 *                 'retried_at' => CarbonImmutable::now()->getTimestamp()];
 *   $this->connection()->hmset($id, ['retried_by' => json_encode($retries)]);
 *
 * Differences from Horizon:
 *   - Uses time() instead of CarbonImmutable::now()->getTimestamp() (identical output)
 *   - Uses phpredis hSet() instead of Laravel's hmset() wrapper (same Redis HSET command)
 *   - Called directly instead of through Laravel's Redis factory
 *
 * The race condition pattern (HGET -> decode -> append -> encode -> HSET) is identical.
 */
function horizon_storeRetryReference(Redis $redis, string $parentKey, string $retryId): void
{
    $retries = json_decode($redis->hGet($parentKey, 'retried_by') ?: '[]');

    $retries[] = [
        'id'         => $retryId,
        'status'     => 'pending',
        'retried_at' => time(),
    ];

    $redis->hSet($parentKey, 'retried_by', json_encode($retries));
}

/**
 * Replicates RedisJobRepository::updateRetryInformationOnParent()
 *
 * Horizon source (5.x branch, src/Repositories/RedisJobRepository.php ~L495):
 *
 *   if ($retries = $this->connection()->hget($payload->retryOf(), 'retried_by')) {
 *       $retries = $this->updateRetryStatus($payload, json_decode($retries, true), $failed);
 *       $this->connection()->hset($payload->retryOf(), 'retried_by', json_encode($retries));
 *   }
 *
 * Where updateRetryStatus() does:
 *   return collect($retries)->map(fn($retry) =>
 *       $retry['id'] === $payload->id()
 *           ? Arr::set($retry, 'status', $failed ? 'failed' : 'completed')
 *           : $retry
 *   )->all();
 *
 * Differences from Horizon:
 *   - Uses array_map() instead of collect()->map()->all() (same result)
 *   - Uses array_merge() instead of Arr::set() (both update 'status' key identically)
 *   - Takes $retryId string instead of JobPayload object (extracts same value)
 *
 * The race condition pattern (HGET -> decode -> modify -> encode -> HSET) is identical.
 */
function horizon_updateRetryInformationOnParent(Redis $redis, string $parentKey, string $retryId, bool $failed): void
{
    if ($retries = $redis->hGet($parentKey, 'retried_by')) {
        $retries = json_decode($retries, true);

        $retries = array_map(function ($retry) use ($retryId, $failed) {
            return $retry['id'] === $retryId
                ? array_merge($retry, ['status' => $failed ? 'failed' : 'completed'])
                : $retry;
        }, $retries);

        $redis->hSet($parentKey, 'retried_by', json_encode($retries));
    }
}

// ---------------------------------------------------------------------------
// Child worker process
// ---------------------------------------------------------------------------

if ($isChild) {
    $workerId = (int) $argv[5];

    $redis = new Redis();
    $redis->connect($redisHost, $redisPort);

    for ($i = 0; $i < $retriesPerWorker; $i++) {
        $retryId = "retry-w{$workerId}-{$i}";

        // Step 1: Store the retry reference (like RetryFailedJob dispatching a retry)
        horizon_storeRetryReference($redis, $parentJobKey, $retryId);

        // Step 2: Mark it completed (like the retry job finishing)
        // In real Horizon, there would be seconds/minutes of job execution between
        // steps 1 and 2. The primary race is between concurrent storeRetryReference()
        // calls (step 1) from different workers, which happens regardless of this gap.
        horizon_updateRetryInformationOnParent($redis, $parentJobKey, $retryId, false);
    }

    exit(0);
}

// ---------------------------------------------------------------------------
// Parent process — orchestrates the reproduction
// ---------------------------------------------------------------------------

echo "\n";
echo "=================================================================\n";
echo "  Horizon Retry Tracking Race Condition — Reproduction\n";
echo "  https://github.com/laravel/horizon/issues/1744\n";
echo "=================================================================\n\n";
echo "  Scenario:  {$workers} workers simultaneously retry failed jobs\n";
echo "             from the same parent (e.g., user clicks 'Retry All')\n";
echo "  Workers:              {$workers}\n";
echo "  Retries per worker:   {$retriesPerWorker}\n";
echo "  Expected total:       {$expectedTotal} retry entries\n";
echo "  Runs:                 {$runs}\n";
echo "  Redis:                {$redisHost}:{$redisPort}\n";
echo "\n";

// Verify Redis connection
$redis = new Redis();
if (!@$redis->connect($redisHost, $redisPort)) {
    echo "ERROR: Cannot connect to Redis at {$redisHost}:{$redisPort}\n";
    echo "Set REDIS_HOST / REDIS_PORT environment variables if needed.\n";
    exit(1);
}

$descriptors = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];

// ---------------------------------------------------------------------------
// Helper: spawn workers and wait for completion
// ---------------------------------------------------------------------------

function spawnWorkers(string $cmd, int $count, array $descriptors): void
{
    $processes = [];

    for ($w = 0; $w < $count; $w++) {
        $fullCmd = str_replace('{WORKER_ID}', (string) $w, $cmd);
        $proc = proc_open($fullCmd, $descriptors, $pipes);
        if (is_resource($proc)) {
            fclose($pipes[0]);
            $processes[] = ['proc' => $proc, 'stdout' => $pipes[1], 'stderr' => $pipes[2]];
        }
    }

    // Wait for ALL children to finish before returning
    foreach ($processes as $p) {
        stream_get_contents($p['stdout']);
        fclose($p['stdout']);
        stream_get_contents($p['stderr']);
        fclose($p['stderr']);
        proc_close($p['proc']);
    }
}

// ---------------------------------------------------------------------------
// Test 1: Non-atomic (current Horizon code) — run multiple times
// ---------------------------------------------------------------------------

echo "--- Test 1: Non-atomic HGET/HSET (current Horizon code) ---------\n\n";

$totalLost = 0;
$totalWrongStatus = 0;
$totalExpected = 0;

for ($run = 1; $run <= $runs; $run++) {
    $key = 'horizon:test:race:' . bin2hex(random_bytes(4));
    $redis->del($key);

    $cmd = PHP_BINARY . ' ' . escapeshellarg(__FILE__)
        . ' ' . $workers
        . ' ' . $retriesPerWorker
        . ' ' . $runs
        . ' --child {WORKER_ID}'
        . ' ' . escapeshellarg($key);

    spawnWorkers($cmd, $workers, $descriptors);

    $raw = $redis->hGet($key, 'retried_by');
    $retries = json_decode($raw ?: '[]', true);
    $actual = count($retries);
    $completed = count(array_filter($retries, fn($r) => ($r['status'] ?? '') === 'completed'));
    $pending = count(array_filter($retries, fn($r) => ($r['status'] ?? '') === 'pending'));
    $lost = $expectedTotal - $actual;

    $totalLost += $lost;
    $totalWrongStatus += $pending;
    $totalExpected += $expectedTotal;

    $lostPct = $expectedTotal > 0 ? round($lost / $expectedTotal * 100) : 0;
    $statusNote = $pending > 0 ? ", {$pending} wrong status" : "";
    echo "    Run {$run}: {$actual}/{$expectedTotal} survived";
    if ($lost > 0) echo " ({$lost} LOST, {$lostPct}%{$statusNote})";
    echo "\n";

    $redis->del($key);
}

$avgLossPct = $totalExpected > 0 ? round($totalLost / $totalExpected * 100, 1) : 0;

echo "\n";
echo "  Average loss: {$avgLossPct}% ({$totalLost}/{$totalExpected} entries lost across {$runs} runs)\n";

if ($totalLost > 0) {
    echo "\n  ** DATA LOSS CONFIRMED **\n";
    echo "  No errors were thrown. The Horizon dashboard would silently\n";
    echo "  show fewer retry entries than actually exist.\n";
} else {
    echo "\n  No data loss detected. Try increasing workers or retries.\n";
}

// ---------------------------------------------------------------------------
// Test 2: Atomic Lua scripts (the fix from PR #1745)
//
// These Lua scripts are byte-for-byte identical to the ones in
// src/LuaScripts.php from the fix/atomic-retry-tracking branch.
// ---------------------------------------------------------------------------

echo "\n--- Test 2: Atomic Lua scripts (PR #1745 fix) --------------------\n\n";

$luaChildScript = sys_get_temp_dir() . '/horizon-race-lua-child-' . getmypid() . '.php';
file_put_contents($luaChildScript, '<?php
$redis = new Redis();
$redis->connect($argv[1], (int)$argv[2]);
$parentKey = $argv[3];
$workerId = (int)$argv[4];
$count = (int)$argv[5];

// Identical to LuaScripts::storeRetryReference() in the PR
$storeLua = <<<\'LUA\'
    local retries = redis.call("hget", KEYS[1], "retried_by")
    local decoded = {}
    if retries then
        decoded = cjson.decode(retries)
    end
    table.insert(decoded, {id = ARGV[1], status = "pending", retried_at = tonumber(ARGV[2])})
    redis.call("hset", KEYS[1], "retried_by", cjson.encode(decoded))
LUA;

// Identical to LuaScripts::updateRetryStatus() in the PR
$updateLua = <<<\'LUA\'
    local retries = redis.call("hget", KEYS[1], "retried_by")
    if not retries then return end
    local decoded = cjson.decode(retries)
    for i, retry in ipairs(decoded) do
        if retry["id"] == ARGV[1] then
            retry["status"] = ARGV[2]
            break
        end
    end
    redis.call("hset", KEYS[1], "retried_by", cjson.encode(decoded))
LUA;

for ($i = 0; $i < $count; $i++) {
    $retryId = "retry-w{$workerId}-{$i}";
    $redis->eval($storeLua, [$parentKey, $retryId, time()], 1);
    $redis->eval($updateLua, [$parentKey, $retryId, "completed"], 1);
}
');

$totalLost2 = 0;

for ($run = 1; $run <= $runs; $run++) {
    $key = 'horizon:test:race:lua:' . bin2hex(random_bytes(4));
    $redis->del($key);

    $cmd = PHP_BINARY
        . ' ' . escapeshellarg($luaChildScript)
        . ' ' . escapeshellarg($redisHost)
        . ' ' . $redisPort
        . ' ' . escapeshellarg($key)
        . ' {WORKER_ID}'
        . ' ' . $retriesPerWorker;

    spawnWorkers($cmd, $workers, $descriptors);

    $raw2 = $redis->hGet($key, 'retried_by');
    $retries2 = json_decode($raw2 ?: '[]', true);
    $actual2 = count($retries2);
    $completed2 = count(array_filter($retries2, fn($r) => ($r['status'] ?? '') === 'completed'));
    $lost2 = $expectedTotal - $actual2;

    $totalLost2 += $lost2;

    echo "    Run {$run}: {$actual2}/{$expectedTotal} survived, {$completed2} completed";
    if ($lost2 > 0) echo " ({$lost2} LOST)";
    echo "\n";

    $redis->del($key);
}

@unlink($luaChildScript);

echo "\n";
if ($totalLost2 === 0) {
    echo "  ** ALL DATA PRESERVED across {$runs} runs ** — Lua scripts eliminate the race.\n";
} else {
    echo "  Lost {$totalLost2} entries (unexpected — Lua should be atomic).\n";
}

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------

echo "\n--- Summary ----------------------------------------------------\n\n";
echo "  Non-atomic (current code):  {$avgLossPct}% average data loss\n";
echo "  Atomic (Lua fix):           " . ($totalLost2 === 0 ? "0% data loss" : "{$totalLost2} entries lost") . "\n";
echo "\n";
echo "  This pattern has existed since Horizon's first commit (2017-07-14).\n";
echo "  The bug is silent — no errors, no exceptions, no log entries.\n";
echo "  Retry entries simply vanish from the dashboard.\n";
echo "\n";
echo "  Fix: https://github.com/laravel/horizon/pull/1745\n";
echo "\n";
