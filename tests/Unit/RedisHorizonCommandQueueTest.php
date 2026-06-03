<?php

namespace Laravel\Horizon\Tests\Unit;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Laravel\Horizon\RedisHorizonCommandQueue;
use Laravel\Horizon\Tests\UnitTest;

class RedisHorizonCommandQueueTest extends UnitTest
{
    public function test_pending_commands_are_drained_atomically()
    {
        $connection = new CommandQueueFakeRedisConnection;
        $queue = new RedisHorizonCommandQueue(new CommandQueueFakeRedisFactory($connection));

        $queue->push('supervisor', FirstCommand::class, ['name' => 'first']);

        $connection->afterDrain(function () use ($queue) {
            $queue->push('supervisor', SecondCommand::class, ['name' => 'second']);
        });

        $pending = $queue->pending('supervisor');

        $this->assertCount(1, $pending);
        $this->assertSame(FirstCommand::class, $pending[0]->command);
        $this->assertSame(['name' => 'first'], $pending[0]->options);

        $pending = $queue->pending('supervisor');

        $this->assertCount(1, $pending);
        $this->assertSame(SecondCommand::class, $pending[0]->command);
        $this->assertSame(['name' => 'second'], $pending[0]->options);
    }
}

class CommandQueueFakeRedisFactory implements RedisFactory
{
    public function __construct(public CommandQueueFakeRedisConnection $connection)
    {
    }

    public function connection($name = null)
    {
        return $this->connection;
    }
}

class CommandQueueFakeRedisConnection
{
    public array $commands = [];

    public $afterDrain;

    public function afterDrain(callable $callback)
    {
        $this->afterDrain = $callback;
    }

    public function rpush($key, $value)
    {
        $this->commands[$key][] = $value;
    }

    public function eval($script, $numberOfKeys, $key)
    {
        $commands = $this->commands[$key] ?? [];

        if (count($commands) > 0) {
            unset($this->commands[$key]);
        }

        if ($this->afterDrain) {
            $callback = $this->afterDrain;
            $this->afterDrain = null;

            $callback();
        }

        return $commands;
    }

    public function lrange($key, $start, $stop)
    {
        $commands = $this->commands[$key] ?? [];

        if ($this->afterDrain) {
            $callback = $this->afterDrain;
            $this->afterDrain = null;

            $callback();
        }

        return $commands;
    }

    public function del($key)
    {
        unset($this->commands[$key]);
    }
}

class FirstCommand
{
}

class SecondCommand
{
}
