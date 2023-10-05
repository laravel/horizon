<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;

class StatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:status {--environment= : The environment name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the current status of Horizon';

    /**
     * Execute the console command.
     *
     * @param  \Laravel\Horizon\Contracts\MasterSupervisorRepository  $masterSupervisorRepository
     * @return int
     */
    public function handle(MasterSupervisorRepository $masterSupervisorRepository)
    {
        $masters = collect($masterSupervisorRepository->all());
        if ($environment = $this->option('environment')) {
            $masters->filter(function ($supervisor) use ($environment) {
                return $supervisor->environment === $environment;
            });
        }

        if ($masters->isEmpty()) {
            $this->error('Horizon is inactive.');

            return 1;
        }

        if ($masters->contains(function ($master) {
            return $master->status === 'paused';
        })) {
            $this->warn('Horizon is paused.');

            return 1;
        }

        $this->info('Horizon is running.');

        return 0;
    }
}
