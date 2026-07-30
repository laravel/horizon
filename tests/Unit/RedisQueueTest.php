<?php

namespace Laravel\Horizon\Tests\Unit;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Queue\RedisQueue as BaseRedisQueue;
use Illuminate\Redis\Connections\Connection;
use Laravel\Horizon\RedisQueue;
use Laravel\Horizon\Tests\UnitTest;
use Mockery;

class RedisQueueTest extends UnitTest
{
    public function test_pending_state_is_read_in_one_redis_call()
    {
        $redis = Mockery::mock(RedisFactory::class);
        $connection = Mockery::mock(Connection::class);

        $redis->shouldReceive('connection')->with('default')->andReturn($connection);

        if ($this->frameworkProvidesQueueRedisKeyHelper()) {
            $connection->shouldReceive('isCluster')->zeroOrMoreTimes()->andReturnFalse();
        }

        $connection->shouldReceive('eval')
            ->once()
            ->withArgs(function ($script, $keyCount, ...$keys) {
                return str_contains($script, "redis.call('llen', KEYS[1])")
                    && str_contains($script, "redis.call('zcard', KEYS[2])")
                    && str_contains($script, "redis.call('zcard', KEYS[3])")
                    && $keyCount === 3
                    && $keys === [
                        'queues:reports',
                        'queues:reports:reserved',
                        'queues:reports:delayed',
                    ];
            })
            ->andReturn([12, 3, 4]);

        $queue = new RedisQueue($redis, 'default', 'default');

        $this->assertSame([
            'ready' => 12,
            'reserved' => 3,
            'delayed' => 4,
        ], $queue->pendingState('reports'));
    }

    public function test_pending_state_keys_share_a_redis_cluster_hash_tag()
    {
        if (! $this->frameworkProvidesQueueRedisKeyHelper()) {
            $this->markTestSkipped(
                'Illuminate\\Queue\\RedisQueue::getQueueRedisKey is not available on this Laravel version.'
            );
        }

        $redis = Mockery::mock(RedisFactory::class);
        $connection = Mockery::mock(Connection::class);

        $redis->shouldReceive('connection')->with('default')->andReturn($connection);
        $connection->shouldReceive('isCluster')->zeroOrMoreTimes()->andReturnTrue();

        $connection->shouldReceive('eval')
            ->once()
            ->withArgs(function ($script, $keyCount, ...$keys) {
                return str_contains($script, "redis.call('llen', KEYS[1])")
                    && str_contains($script, "redis.call('zcard', KEYS[2])")
                    && str_contains($script, "redis.call('zcard', KEYS[3])")
                    && $keyCount === 3
                    && $keys === [
                        'queues:{reports}',
                        'queues:{reports}:reserved',
                        'queues:{reports}:delayed',
                    ];
            })
            ->andReturn([0, 0, 0]);

        $queue = new RedisQueue($redis, 'default', 'default');

        $this->assertSame([
            'ready' => 0,
            'reserved' => 0,
            'delayed' => 0,
        ], $queue->pendingState('reports'));
    }

    protected function frameworkProvidesQueueRedisKeyHelper()
    {
        return method_exists(BaseRedisQueue::class, 'getQueueRedisKey');
    }
}
