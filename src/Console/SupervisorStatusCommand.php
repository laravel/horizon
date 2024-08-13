<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\MasterSupervisor;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'horizon:supervisor-status')]
class SupervisorStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:supervisor-status
                            {name : The name of the supervisor}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the status for a given supervisor';

    /**
     * Execute the console command.
     *
     * @param  \Laravel\Horizon\Contracts\SupervisorRepository  $supervisors
     * @return void
     */
    public function handle(SupervisorRepository $supervisors)
    {
        $name = $this->argument('name');

        $supervisorStatus = optional(collect($supervisors->all())->first(function ($supervisor) use ($name) {
            return Str::startsWith($supervisor->name, MasterSupervisor::basename()) &&
                   Str::endsWith($supervisor->name, $name);
        }))->status;

        if (is_null($supervisorStatus)) {
            $this->components->error('Unable to find a supervisor with this name.');

            return 1;
        }

        $this->components->info("{$name} is {$supervisorStatus}");
    }
}
