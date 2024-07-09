<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Arr;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\RedisQueue;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'horizon:clear')]
class ClearCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:clear
                            {--queue= : The name of the queue to clear}
                            {--all-queues : Clear all queues}
                            {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all of the jobs from the specified queue';

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle(JobRepository $jobRepository, QueueManager $manager)
    {
        if (! $this->confirmToProceed()) {
            return 1;
        }

        if (! method_exists(RedisQueue::class, 'clear')) {
            $this->components->error('Clearing queues is not supported on this version of Laravel.');

            return 1;
        }

        $connection = Arr::first($this->laravel['config']->get('horizon.defaults'))['connection'] ?? 'redis';

        $queues = $this->getQueues($connection);

        foreach ($queues as $queue) {
            if (method_exists($jobRepository, 'purge')) {
                $jobRepository->purge($queue);
            }

            $count = $manager->connection($connection)->clear($queue);

            $this->components->info('Cleared '.$count.' jobs from the ['.$queue.'] queue.');
        }

        return 0;
    }

    /**
     * Get the queue names to clear.
     *
     * @return array<string>
     */
    protected function getQueues(string $connection): array
    {
        if ($this->option('queue')) {
            $queues = [$this->option('queue')];
        } else {
            $queues = [$this->laravel['config']->get("queue.connections.{$connection}.queue", 'default')];

            if ($this->option('all-queues')) {
                $supervisors = $this->laravel['config']->get('horizon.defaults', []);
                foreach ($supervisors as $supervisor) {
                    $queues = array_merge($queues, Arr::wrap($supervisor['queue'] ?? []));
                }
                $queues = array_values(array_unique($queues));
            }
        }

        return $queues;
    }
}
