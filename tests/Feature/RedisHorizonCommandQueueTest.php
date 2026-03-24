<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\RedisHorizonCommandQueue;
use Laravel\Horizon\Tests\IntegrationTest;

class RedisHorizonCommandQueueTest extends IntegrationTest
{
    public function test_pending_returns_all_pushed_commands()
    {
        $queue = $this->app->make(RedisHorizonCommandQueue::class);

        $queue->push('test-supervisor', 'Scale', ['processes' => 5]);
        $queue->push('test-supervisor', 'Balance', []);
        $queue->push('test-supervisor', 'Pause', []);

        $commands = $queue->pending('test-supervisor');

        $this->assertCount(3, $commands);
        $this->assertEquals('Scale', $commands[0]->command);
        $this->assertEquals('Balance', $commands[1]->command);
        $this->assertEquals('Pause', $commands[2]->command);
    }

    public function test_pending_consumes_commands_atomically()
    {
        $queue = $this->app->make(RedisHorizonCommandQueue::class);

        $queue->push('test-supervisor', 'Scale', ['processes' => 5]);
        $queue->push('test-supervisor', 'Terminate', []);

        $first = $queue->pending('test-supervisor');
        $second = $queue->pending('test-supervisor');

        $this->assertCount(2, $first);
        $this->assertEmpty($second);
    }

    public function test_pending_returns_empty_when_no_commands()
    {
        $queue = $this->app->make(RedisHorizonCommandQueue::class);

        $this->assertEmpty($queue->pending('nonexistent-supervisor'));
    }

    public function test_pending_preserves_command_options()
    {
        $queue = $this->app->make(RedisHorizonCommandQueue::class);

        $queue->push('test-supervisor', 'Scale', ['processes' => 10, 'queue' => 'default']);

        $commands = $queue->pending('test-supervisor');

        $this->assertCount(1, $commands);
        $this->assertEquals(10, $commands[0]->options['processes']);
        $this->assertEquals('default', $commands[0]->options['queue']);
    }

    public function test_flush_removes_all_commands()
    {
        $queue = $this->app->make(RedisHorizonCommandQueue::class);

        $queue->push('test-supervisor', 'Scale', []);
        $queue->push('test-supervisor', 'Balance', []);
        $queue->flush('test-supervisor');

        $this->assertEmpty($queue->pending('test-supervisor'));
    }

    public function test_commands_are_isolated_between_supervisors()
    {
        $queue = $this->app->make(RedisHorizonCommandQueue::class);

        $queue->push('supervisor-a', 'Scale', []);
        $queue->push('supervisor-b', 'Terminate', []);

        $a = $queue->pending('supervisor-a');
        $b = $queue->pending('supervisor-b');

        $this->assertCount(1, $a);
        $this->assertEquals('Scale', $a[0]->command);
        $this->assertCount(1, $b);
        $this->assertEquals('Terminate', $b[0]->command);
    }
}
