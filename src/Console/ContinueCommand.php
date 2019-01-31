<?php

namespace Laravel\Horizon\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;

class ContinueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:continue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Instruct the master supervisor to continue processing jobs';

    /**
     * Execute the console command.
     *
     * @param  \Laravel\Horizon\Contracts\MasterSupervisorRepository  $masters
     * @return void
     */
    public function handle(MasterSupervisorRepository $masters)
    {
        collect($masters->all())
            ->filter(function ($master) {
                return Str::startsWith($master->name, MasterSupervisor::basename());
            })
            ->pluck('pid')
            ->each(function ($processId) {
                $this->info("Sending CONT Signal To Process: {$processId}");

                if (! posix_kill($processId, SIGCONT)) {
                    $this->error("Failed to kill process: {$processId} (".posix_strerror(posix_get_last_error()).')');
                }
            });
    }
}
