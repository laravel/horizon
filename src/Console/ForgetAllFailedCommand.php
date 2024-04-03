<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Laravel\Horizon\Contracts\JobRepository;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'horizon:forget-all-failed-jobs')]
class ForgetAllFailedCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'horizon:forget-all-failed-jobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all failed jobs';

    /**
     * Execute the console command.
     *
     * @return null
     */
    public function handle(JobRepository $repository): void
    {
        $totalCount = $repository->totalFailed();

        do {
            collect($repository->getFailed())->pluck('id')->each(function ($failedId) use ($repository): void {
                $repository->deleteFailed($failedId);

                if ($this->laravel['queue.failer']->forget($failedId)) {
                    $this->components->info('Failed job (id): '.$failedId.' deleted successfully!');
                }
            });
        } while ($repository->totalFailed() !== 0);

        if ($totalCount) {
            $this->components->info('All failed jobs ('.$totalCount.') deleted successfully!');
        } else {
            $this->components->info('Nothing to be deleted as failed jobs are empty');
        }
    }
}