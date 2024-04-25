<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Laravel\Horizon\Contracts\JobRepository;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'horizon:forget')]
class ForgetFailedCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'horizon:forget {id? : The ID of the failed job} {--all : Delete all failed jobs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a failed queue job';

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle(JobRepository $repository)
    {
        if ($this->option('all')) {
            $totalFailedCount = $repository->totalFailed();

            do {
                $failedJobs = collect($repository->getFailed());

                $failedJobs->pluck('id')->each(function ($failedId) use ($repository): void {
                    $repository->deleteFailed($failedId);

                    if ($this->laravel['queue.failer']->forget($failedId)) {
                        $this->components->info('Failed job (id): '.$failedId.' deleted successfully!');
                    }
                });
            } while ($repository->totalFailed() !== 0 && $failedJobs->isNotEmpty());

            if ($totalFailedCount) {
                $this->components->info($totalFailedCount.' failed jobs deleted successfully!');
            } else {
                $this->components->info('No failed jobs detected.');
            }

            return;
        }

        if (! $this->argument('id')) {
            $this->components->error('No failed job ID provided.');
        }

        $repository->deleteFailed($this->argument('id'));

        if ($this->laravel['queue.failer']->forget($this->argument('id'))) {
            $this->components->info('Failed job deleted successfully!');
        } else {
            $this->components->error('No failed job matches the given ID.');

            return 1;
        }
    }
}
