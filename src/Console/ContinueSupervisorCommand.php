<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\MasterSupervisor;

class ContinueSupervisorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:continue-supervisor
                            {name : The name of the supervisor to resume}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Instruct the supervisor to continue processing jobs';

    /**
     * Execute the console command.
     *
     * @param  \Laravel\Horizon\Contracts\SupervisorRepository  $supervisors
     * @return void
     */
    public function handle(SupervisorRepository $supervisors)
    {
        $processId = optional(collect($supervisors->all())->first(function ($supervisor) {
            return Str::startsWith($supervisor->name, MasterSupervisor::basename())
                    && Str::endsWith($supervisor->name, $this->argument('name'));
        }))->pid;

        if (is_null($processId)) {
            $this->error('Failed to find a supervisor with this name');

            return 1;
        }

        $this->info("Sending CONT Signal To Process: {$processId}");

        if (! posix_kill($processId, SIGCONT)) {
            $this->error("Failed to send CONT signal to process: {$processId} (".posix_strerror(posix_get_last_error()).')');
        }
    }
}
