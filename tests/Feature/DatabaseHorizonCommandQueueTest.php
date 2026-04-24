<?php

namespace Laravel\Horizon\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Contracts\HorizonCommandQueue;
use Laravel\Horizon\DatabaseHorizonCommandQueue;
use Laravel\Horizon\Tests\DatabaseIntegrationTest;

class DatabaseHorizonCommandQueueTest extends DatabaseIntegrationTest
{
    protected function queue(): DatabaseHorizonCommandQueue
    {
        return $this->app->make(HorizonCommandQueue::class);
    }

    public function test_binding_resolves_to_database_implementation()
    {
        $this->assertInstanceOf(DatabaseHorizonCommandQueue::class, $this->queue());
    }

    public function test_push_and_pending_return_commands_in_fifo_order()
    {
        $queue = $this->queue();

        $queue->push('master', 'Pause', ['reason' => 'deploy']);
        $queue->push('master', 'Continue');
        $queue->push('master', 'Terminate');

        $commands = $queue->pending('master');

        $this->assertCount(3, $commands);
        $this->assertSame('Pause', $commands[0]->command);
        $this->assertSame(['reason' => 'deploy'], $commands[0]->options);
        $this->assertSame('Continue', $commands[1]->command);
        $this->assertSame('Terminate', $commands[2]->command);
    }

    public function test_pending_drains_the_queue()
    {
        $queue = $this->queue();

        $queue->push('master', 'Pause');

        $this->assertCount(1, $queue->pending('master'));
        $this->assertSame([], $queue->pending('master'));
    }

    public function test_pending_returns_empty_array_for_unknown_queue()
    {
        $this->assertSame([], $this->queue()->pending('nonexistent'));
    }

    public function test_pending_isolates_queues_by_name()
    {
        $queue = $this->queue();

        $queue->push('master', 'Pause');
        $queue->push('supervisor-1', 'Terminate');

        $this->assertCount(1, $queue->pending('master'));
        $this->assertCount(1, $queue->pending('supervisor-1'));
    }

    public function test_pending_executes_read_and_delete_within_a_single_transaction()
    {
        $queue = $this->queue();
        $queue->push('master', 'Pause');

        $levels = [];

        DB::listen(function ($query) use (&$levels) {
            $sql = strtolower(trim($query->sql));

            if (! str_contains($sql, 'horizon_commands')) {
                return;
            }

            if (str_starts_with($sql, 'select')) {
                $levels['select'] = DB::transactionLevel();
            } elseif (str_starts_with($sql, 'delete')) {
                $levels['delete'] = DB::transactionLevel();
            }
        });

        $queue->pending('master');

        $this->assertSame(1, $levels['select'] ?? null, 'pending() must SELECT inside a transaction');
        $this->assertSame(1, $levels['delete'] ?? null, 'pending() must DELETE inside the same transaction');
    }

    public function test_pending_does_not_drop_commands_pushed_between_two_pending_calls()
    {
        $queue = $this->queue();

        $queue->push('master', 'Pause');
        $first = $queue->pending('master');

        $queue->push('master', 'Continue');
        $second = $queue->pending('master');

        $this->assertCount(1, $first);
        $this->assertSame('Pause', $first[0]->command);
        $this->assertCount(1, $second);
        $this->assertSame('Continue', $second[0]->command);
    }

    public function test_flush_removes_all_commands_for_queue()
    {
        $queue = $this->queue();

        $queue->push('master', 'Pause');
        $queue->push('master', 'Continue');

        $queue->flush('master');

        $this->assertSame([], $queue->pending('master'));
    }

}
