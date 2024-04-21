<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Laravel\Horizon\RedisQueue;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'horizon:clear')]
class ClearlAllCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:clear_all
                            {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all of the jobs from the all queues 😱';

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return 1;
        }

        if (! method_exists(RedisQueue::class, 'clear')) {
            $this->components->error('Clearing all queues is not supported on this version of Laravel.');

            return 1;
        }

        $queues = config('horizon.defaults');
        foreach ($queues as $keyName => $queue) {
            $names = data_get($queue, 'queue', []);
            foreach ($names as $name) {
                $this->info("Clearing queue: $name");
                $this->call('horizon:clear', [$name, '--force' => true]);
            }
        }

        $this->components->info('Done 🙌🏻');

        return 0;
    }

    /**
     * Get the queue name to clear.
     *
     * @param  string  $connection
     * @return string
     */
    protected function getQueue($connection)
    {
        return $this->option('queue') ?: $this->laravel['config']->get(
            "queue.connections.{$connection}.queue",
            'default'
        );
    }
}
