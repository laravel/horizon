<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Queue\RedisQueue as BaseRedisQueue;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Horizon\RedisQueue;
use Laravel\Horizon\Tests\IntegrationTest;
use ReflectionMethod;

class PendingStateTest extends IntegrationTest
{
    public function test_pending_state_counts_ready_reserved_and_delayed_jobs_from_redis()
    {
        $queue = Queue::connection('redis');

        $this->assertInstanceOf(RedisQueue::class, $queue);

        // Cluster CI sets both REDIS_CLUSTER_HOSTS_AND_PORTS and REDIS_CLIENT; the helper
        // rewrites database.redis.client from getenv so this catches client mix-ups there.
        if (getenv('REDIS_CLUSTER_HOSTS_AND_PORTS') && ($expectedClient = getenv('REDIS_CLIENT') ?: null)) {
            $this->assertSame(
                $expectedClient,
                config('database.redis.client'),
                'Configured Redis client should honor REDIS_CLIENT under the cluster test harness.'
            );
        }

        $queueName = 'pending-state-'.Str::lower(Str::random(16));
        $readyKey = $this->physicalQueueKey($queue, $queueName);
        $reservedKey = $readyKey.':reserved';
        $delayedKey = $readyKey.':delayed';
        $connection = $queue->getConnection();

        try {
            $connection->del($readyKey, $reservedKey, $delayedKey);

            $connection->rpush($readyKey, 'ready-job-1');
            $connection->rpush($readyKey, 'ready-job-2');
            $connection->rpush($readyKey, 'ready-job-3');

            $connection->zadd($reservedKey, 1, 'reserved-job-1');
            $connection->zadd($reservedKey, 2, 'reserved-job-2');

            $connection->zadd($delayedKey, 10, 'delayed-job-1');
            $connection->zadd($delayedKey, 20, 'delayed-job-2');
            $connection->zadd($delayedKey, 30, 'delayed-job-3');
            $connection->zadd($delayedKey, 40, 'delayed-job-4');

            $this->assertSame([
                'ready' => 3,
                'reserved' => 2,
                'delayed' => 4,
            ], $queue->pendingState($queueName));
        } finally {
            $connection->del($readyKey, $reservedKey, $delayedKey);
        }
    }

    /**
     * Resolve the physical Redis key family pendingState reads for a queue name.
     */
    private function physicalQueueKey(RedisQueue $queue, string $queueName): string
    {
        $methodName = method_exists(BaseRedisQueue::class, 'getQueueRedisKey')
            ? 'getQueueRedisKey'
            : 'getQueue';

        $method = new ReflectionMethod($queue, $methodName);
        $method->setAccessible(true);

        return $method->invoke($queue, $queueName);
    }
}
