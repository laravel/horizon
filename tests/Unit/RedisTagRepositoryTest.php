<?php

namespace Laravel\Horizon\Tests\Unit;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PredisConnection;
use Laravel\Horizon\Repositories\RedisTagRepository;
use Laravel\Horizon\Tests\UnitTest;
use Mockery;

class RedisTagRepositoryTest extends UnitTest
{
    public function test_empty_tags_do_not_read_redis()
    {
        $redis = Mockery::mock(RedisFactory::class);
        $redis->shouldNotReceive('connection');

        $this->assertSame([], (new RedisTagRepository($redis))->monitored([]));
    }

    public function test_only_requested_tags_are_checked_and_keys_and_duplicates_are_preserved()
    {
        $connection = Mockery::mock(PredisConnection::class);
        $connection->shouldNotReceive('smembers');
        $connection->shouldReceive('eval')->once()->with(
            Mockery::type('string'), 1, 'monitoring', 'first', 'second', 'first'
        )->andReturn([1, 0, 1]);

        $this->assertSame(
            [3 => 'first', 'last' => 'first'],
            $this->repository($connection)->monitored([3 => 'first', 7 => 'second', 'last' => 'first'])
        );
    }

    public function test_unknown_connection_types_keep_the_existing_lookup()
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('smembers')->once()->with('monitoring')->andReturn(['second']);

        $this->assertSame([7 => 'second'], $this->repository($connection)->monitored([3 => 'first', 7 => 'second']));
    }

    public function test_phpredis_client_encoding_uses_the_existing_lookup()
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('The Redis extension is not installed.');
        }

        foreach ([[\Redis::SERIALIZER_PHP, \Redis::COMPRESSION_NONE], [\Redis::SERIALIZER_NONE, 1]] as [$serializer, $compression]) {
            $client = Mockery::mock(\Redis::class);
            $client->shouldReceive('getOption')->with(\Redis::OPT_SERIALIZER)->andReturn($serializer);
            $client->shouldReceive('getOption')->with(\Redis::OPT_COMPRESSION)->andReturn($compression);
            $connection = Mockery::mock(PhpRedisConnection::class);
            $connection->shouldReceive('client')->andReturn($client);
            $connection->shouldReceive('smembers')->once()->with('monitoring')->andReturn(['first']);
            $connection->shouldNotReceive('command')->with('eval', Mockery::any());

            $this->assertSame([3 => 'first'], $this->repository($connection)->monitored([3 => 'first', 7 => 'second']));
        }
    }

    public function test_phpredis_without_encoding_uses_lua()
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('The Redis extension is not installed.');
        }

        $client = Mockery::mock(\Redis::class);
        $client->shouldReceive('getOption')->with(\Redis::OPT_SERIALIZER)->andReturn(\Redis::SERIALIZER_NONE);
        $client->shouldReceive('getOption')->with(\Redis::OPT_COMPRESSION)->andReturn(\Redis::COMPRESSION_NONE);
        $connection = Mockery::mock(PhpRedisConnection::class);
        $connection->shouldReceive('client')->andReturn($client);
        $connection->shouldNotReceive('smembers');
        $connection->shouldReceive('command')->once()->withArgs(function ($method, $arguments) {
            return $method === 'eval' && is_string($arguments[0])
                && $arguments[1] === ['monitoring', 'first'] && $arguments[2] === 1;
        })->andReturn([1]);

        $this->assertSame(['first'], $this->repository($connection)->monitored(['first']));
    }

    public function test_phpredis_cluster_client_keeps_the_existing_lookup()
    {
        $connection = Mockery::mock(PhpRedisConnection::class);
        $connection->shouldReceive('client')->andReturn(new \stdClass);
        $connection->shouldReceive('smembers')->once()->with('monitoring')->andReturn(['first']);
        $connection->shouldNotReceive('command')->with('eval', Mockery::any());

        $this->assertSame(['first'], $this->repository($connection)->monitored(['first']));
    }

    protected function repository($connection)
    {
        $redis = Mockery::mock(RedisFactory::class);
        $redis->shouldReceive('connection')->with('horizon')->andReturn($connection);

        return new RedisTagRepository($redis);
    }
}
