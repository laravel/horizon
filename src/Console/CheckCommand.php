<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\MasterSupervisor;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensure Horizon is running on the current machine';

    /**
     * Execute the console command.
     *
     * @param  \Laravel\Horizon\Contracts\MasterSupervisorRepository  $masterSupervisorRepository
     * @return int
     */
    public function handle(MasterSupervisorRepository $masterSupervisorRepository)
    {
        // If no master supervisor is running, we exit with an error.
        if (! static::isMasterSupervisorRunningOnMachine($masterSupervisorRepository)) {
            $this->error('No master supervisor is running on this machine at the moment.');

            return 1;
        }

        // If everything is running as expected, we return with success.
        $this->line('At least one master supervisor is running on this machine as expected.', null, OutputInterface::VERBOSITY_VERBOSE);

        return 0;
    }

    /**
     * Determine if a master supervisor is running on the current machine.
     *
     * @param  \Laravel\Horizon\Contracts\MasterSupervisorRepository  $masterSupervisorRepository
     * @return bool
     */
    protected static function isMasterSupervisorRunningOnMachine(MasterSupervisorRepository $masterSupervisorRepository)
    {
        if (blank($masters = $masterSupervisorRepository->all())) {
            return false;
        }

        return collect($masters)->contains(function (object $master) {
            return Str::startsWith($master->name, MasterSupervisor::basename());
        });
    }
}
