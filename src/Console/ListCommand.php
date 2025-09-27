<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'horizon:list')]
class ListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:list 
                            {--json : Output all of the deployed machines information as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all of the deployed machines';

    /**
     * Execute the console command.
     *
     * @param  \Laravel\Horizon\Contracts\MasterSupervisorRepository  $masters
     * @return void
     */
    public function handle(MasterSupervisorRepository $masters)
    {
        $masters = $masters->all();

        $this->option('json')
            ? $this->displayJson($masters)
            : $this->displayForCli($masters);
    }

    /**
     * Render all of the deployed machines information as JSON.
     *
     * @param  array  $masters
     * @return void
     */
    protected function displayJson(array $masters)
    {
        if (empty($masters)) {
            return $this->output->writeln('[]');
        }

        $this->output->writeln(collect($masters)->map(function ($master) {
            return [
                'name' => $master->name,
                'pid' => $master->pid,
                'supervisors' => $master->supervisors ? collect($master->supervisors)->map(function ($supervisor) {
                    return explode(':', $supervisor, 2)[1];
                })->implode(', ') : 'None',
                'status' => $master->status,
            ];
        })->toJson());
    }

    /**
     * Render all of the deployed machines information for the CLI.
     *
     * @param  array  $masters
     * @return void
     */
    protected function displayForCli(array $masters)
    {
        if (empty($masters)) {
            return $this->components->info('No machines are running.');
        }

        $this->output->writeln('');

        $this->table([
            'Name', 'PID', 'Supervisors', 'Status',
        ], collect($masters)->map(function ($master) {
            return [
                $master->name,
                $master->pid,
                $master->supervisors ? collect($master->supervisors)->map(function ($supervisor) {
                    return explode(':', $supervisor, 2)[1];
                })->implode(', ') : 'None',
                $master->status,
            ];
        })->all());

        $this->output->writeln('');
    }
}
