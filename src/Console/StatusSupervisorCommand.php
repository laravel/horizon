<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\MasterSupervisor;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'horizon:pause-supervisor')]
class StatusSupervisorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:status-supervisor
                            {name : The name of the supervisor to show status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show status for a supervisor';

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
            return Str::startsWith($supervisor->name, MasterSupervisor::basename())
                    && Str::endsWith($supervisor->name, $name);
        }))->status;

        if (is_null($supervisorStatus)) {
            $this->components->error("Failed to find a supervisor with this name: {$name}");

            return 1;
        }

        $this->components->info("{$name} is {$supervisorStatus}");
    }
}
