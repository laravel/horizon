<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Arr;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\RedisQueue;

class ForgetPendingCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:forget-pending
                            {jobId : The id of the job to clear}
                            {--queue= : The name of the queue where the job is located}
                            {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Delete a specific pending job by its ID from the specified queue";

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

        if (method_exists($jobRepository, 'purgeSpecificJob')) {
            $jobRepository->purgePending($queue = $this->getQueue($connection), $this->argument('jobId'));
        }

        $this->components->info('Cleared pending job ['.$this->argument('jobId').'] from the ['.$queue.'] queue.');

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
