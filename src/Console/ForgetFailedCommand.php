<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Laravel\Horizon\Contracts\JobRepository;

class ForgetFailedCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'horizon:forget {id : The ID of the failed job}';

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
        $repository->deleteFailed($this->argument('id'));

        if ($this->laravel['queue.failer']->forget($this->argument('id'))) {
            $this->components->info('Failed job deleted successfully!');
        } else {
            $this->components->error('No failed job matches the given ID.');

            return 1;
        }
    }
}
