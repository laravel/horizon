<?php

namespace Feature;

use Laravel\Horizon\Repositories\RedisJobRepository;
use Illuminate\Queue\QueueManager;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Tests\IntegrationTest;

class ClearCommandTest extends IntegrationTest
{
    public function test_clear_command_on_single_queue()
    {
        config(['queue.connections.redis.queue' => 'default']);
        $mock = $this->mock(
            RedisJobRepository::class,
            function (\Mockery\MockInterface $mock) {
                $mock->shouldReceive('purge')->twice()->andReturnTrue();
            }
        );
        $this->app->offsetSet(JobRepository::class, $mock);

        $mock = $this->mock(
            QueueManager::class,
            function (\Mockery\MockInterface $mock) {
                $mock->shouldReceive('connection')->twice()->andReturnSelf();
                $mock->shouldReceive('clear')->twice()->andReturn(1);
            }
        );
        $this->app->offsetSet(QueueManager::class, $mock);

        $this->artisan('horizon:clear')->expectsOutputToContain('Cleared 1 jobs from the [default] queue.');

        $this->artisan('horizon:clear', ['--queue' => 'foo'])->expectsOutputToContain('Cleared 1 jobs from the [foo] queue.');
    }

    public function test_clear_command_on_all_queues()
    {
        config(['queue.connections.redis.queue' => 'default']);
        config(['horizon.defaults' => [
            'supervisor-1' => ['queue' => ['a', 'b', 'c']],
            'supervisor-2' => ['queue' => ['c', 'd', 'e', 'f']],
            'supervisor-3' => ['queue' => 'foo'],
        ]]);

        $mock = $this->mock(
            RedisJobRepository::class,
            function (\Mockery\MockInterface $mock) {
                $mock->shouldReceive('purge')->times(8)->andReturnTrue();
            }
        );
        $this->app->offsetSet(JobRepository::class, $mock);

        $mock = $this->mock(
            QueueManager::class,
            function (\Mockery\MockInterface $mock) {
                $mock->shouldReceive('connection')->times(8)->andReturnSelf();
                $mock->shouldReceive('clear')->times(8)->andReturn(1);
            }
        );
        $this->app->offsetSet(QueueManager::class, $mock);

        $this->artisan('horizon:clear', ['--all-queues' => true])
            ->expectsOutputToContain('Cleared 1 jobs from the [default] queue.')
            ->expectsOutputToContain('Cleared 1 jobs from the [a] queue.')
            ->expectsOutputToContain('Cleared 1 jobs from the [b] queue.')
            ->expectsOutputToContain('Cleared 1 jobs from the [c] queue.')
            ->expectsOutputToContain('Cleared 1 jobs from the [d] queue.')
            ->expectsOutputToContain('Cleared 1 jobs from the [e] queue.')
            ->expectsOutputToContain('Cleared 1 jobs from the [f] queue.')
            ->expectsOutputToContain('Cleared 1 jobs from the [foo] queue.')
        ;
    }
}
