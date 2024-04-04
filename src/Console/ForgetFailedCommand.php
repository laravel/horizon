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
    protected $signature = 'horizon:forget {id? : The ID of the failed job} {--all : To delete all failed jobs}';

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
                collect($repository->getFailed())->pluck('id')->each(function ($failedId) use ($repository): void {
                    $repository->deleteFailed($failedId);

                    if ($this->laravel['queue.failer']->forget($failedId)) {
                        $this->components->info('Failed job (id): '.$failedId.' deleted successfully!');
                    }
                });
            } while ($repository->totalFailed() !== 0);

            if ($totalFailedCount) {
                $this->components->info('All failed jobs ('.$totalFailedCount.') deleted successfully!');
            } else {
                $this->components->info('Nothing to be deleted as failed jobs are empty');
            }

            return;
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
