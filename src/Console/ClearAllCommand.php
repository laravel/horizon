<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\ProvisioningPlan;
use Laravel\Horizon\RedisQueue;
use Laravel\Horizon\Repositories\RedisJobRepository;

class ClearAllCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:clear-all
                            {supervisor?* : The name(s) of the supervisors whose queues to clear [default: all]}
                            {--environment= : The environment name}
                            {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all jobs from queues configured for the specified supervisor(s)';

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle(RedisJobRepository $jobRepository, QueueManager $manager)
    {
        if (!$this->confirmToProceed()) {
            return 1;
        }

        if (!method_exists(RedisQueue::class, 'clear')) {
            $this->line('<error>Clearing queues is not supported on this version of Laravel</error>');

            return 1;
        }

        $queues = $this->getQueues(
            $this->option('environment') ?? config('horizon.env') ?? config('app.env'),
            $this->argument('supervisor')
        );

        $connection = Arr::first($this->laravel['config']->get('horizon.defaults'))['connection'] ?? 'redis';
        $count = 0;
        foreach ($queues as $queue) {
            $jobRepository->purge($queue);
            $count += ($manager->connection($connection)->clear($queue));
        }

        $this->line('<info>Cleared ' . $count . ' jobs</info>');

        return 0;
    }

    /**
     * Get all the queues for the given environment and supervisors.
     *
     * @param  string $environment
     * @param  array $supervisors
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getQueues($environment, $supervisors)
    {
        return collect(ProvisioningPlan::get(MasterSupervisor::name())->toSupervisorOptions())
            ->first(function ($_, $name) use ($environment) {
                return Str::is($name, $environment);
            })
            ->only($supervisors ?: null)
            ->pluck('queue')
            ->map(function ($queues) {
                return explode(',', $queues);
            })
            ->flatten()
            ->unique();
    }
}
