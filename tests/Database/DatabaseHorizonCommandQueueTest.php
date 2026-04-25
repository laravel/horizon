<?php

namespace Laravel\Horizon\Tests\Database;

use Laravel\Horizon\Contracts\HorizonCommandQueue;

class DatabaseHorizonCommandQueueTest extends DatabaseTestCase
{
    public function test_commands_can_be_pushed_and_drained_in_order()
    {
        $queue = resolve(HorizonCommandQueue::class);

        $queue->push('worker:1', 'PauseSupervisor', ['name' => 'foo']);
        $queue->push('worker:1', 'ContinueSupervisor', ['name' => 'foo']);
        $queue->push('worker:2', 'PauseSupervisor', ['name' => 'bar']);

        $commands = $queue->pending('worker:1');

        $this->assertCount(2, $commands);
        $this->assertSame('PauseSupervisor', $commands[0]->command);
        $this->assertSame(['name' => 'foo'], $commands[0]->options);
        $this->assertSame('ContinueSupervisor', $commands[1]->command);

        $this->assertSame([], $queue->pending('worker:1'));
        $this->assertCount(1, $queue->pending('worker:2'));
    }

    public function test_a_queue_can_be_flushed()
    {
        $queue = resolve(HorizonCommandQueue::class);

        $queue->push('worker:1', 'PauseSupervisor', []);
        $queue->push('worker:1', 'ContinueSupervisor', []);

        $queue->flush('worker:1');

        $this->assertSame([], $queue->pending('worker:1'));
    }
}
