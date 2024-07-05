<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'horizon:status')]
class StatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:status';

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
        if (! $masters = $masterSupervisorRepository->all()) {
            $this->components->error('Horizon is inactive.');

            return 2;
        }

        if (collect($masters)->contains(function ($master) {
            return $master->status === 'paused';
        })) {
            $this->components->warn('Horizon is paused.');

            return 1;
        }

        $this->components->info('Horizon is running.');

        return 0;
    }
}
