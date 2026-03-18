<?php

namespace Laravel\Horizon\Console;

use Illuminate\Bus\UniqueLock;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Queue\ShouldBeUnique;
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
                            {connection? : The name of the queue connection}
                            {--queue= : The name of the queue to clear}
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

        $connection = $this->argument('connection')
            ?: Arr::first($this->laravel['config']->get('horizon.defaults'))['connection'] ?? 'redis';

        $queue = $this->getQueue($connection);

        $this->releaseUniqueJobLocks($manager, $connection, $queue);

        if (method_exists($jobRepository, 'purge')) {
            $jobRepository->purge($queue);
        }

        $count = $manager->connection($connection)->clear($queue);

        $this->components->info('Cleared '.$count.' jobs from the ['.$queue.'] queue.');

        return 0;
    }

    /**
     * Release any unique job locks for jobs on the given queue.
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @param  string  $connection
     * @param  string  $queue
     * @return void
     */
    protected function releaseUniqueJobLocks(QueueManager $manager, $connection, $queue)
    {
        $redisQueue = $manager->connection($connection);

        $redisConnection = $redisQueue->getConnection();
        $queueKey = $redisQueue->getQueue($queue);

        $payloads = collect()
            ->merge($redisConnection->lrange($queueKey, 0, -1))
            ->merge($redisConnection->zrangebyscore($queueKey.':delayed', '-inf', '+inf'))
            ->merge($redisConnection->zrangebyscore($queueKey.':reserved', '-inf', '+inf'));

        $cache = $this->laravel->make(Cache::class);

        $payloads->each(function ($payload) use ($cache) {
            $this->releaseUniqueJobLock($cache, $payload);
        });
    }

    /**
     * Release the unique job lock for the given payload if applicable.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @param  string  $payload
     * @return void
     */
    protected function releaseUniqueJobLock(Cache $cache, $payload)
    {
        try {
            $decoded = json_decode($payload, true);

            if (! isset($decoded['data']['command'])) {
                return;
            }

            $command = unserialize($decoded['data']['command']);

            if ($command instanceof ShouldBeUnique) {
                (new UniqueLock($cache))->release($command);
            }
        } catch (\Throwable) {
            // If the job payload can't be decoded or unserialized, skip it.
        }
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
