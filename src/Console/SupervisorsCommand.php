<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Laravel\Horizon\Contracts\SupervisorRepository;

class SupervisorsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:supervisors {--individual=} {--ping}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all of the supervisors';

    /**
     * Execute the console command.
     *
     * @param  \Laravel\Horizon\Contracts\SupervisorRepository  $supervisorsRepository
     * @return void
     */
    public function handle(SupervisorRepository $supervisorsRepository)
    {
        $supervisors = collect($supervisorsRepository->all());

        if ($supervisors->count() == 0) {
            return $this->info('No supervisors are running.');
        }

        if ($this->option('ping')) {
            $activeRunningSupervisor = $supervisors->where('status', 'running')->count();

            return $this->info('Total active running supervisors ' . $activeRunningSupervisor);
        }

        $individualSupervisorName = $this->option('individual');

        if ($individualSupervisorName) {
            $individualSupervisor = $supervisorsRepository->find($individualSupervisorName);

            if ($individualSupervisor) {
                $headers = [
                            'Name',
                            'PID', 
                            'Status',
                            'Workers',
                            'Balancing',
                            'Connection',
                            'Queue',
                            'Delay',
                            'Force',
                            'Max Processes',
                            'Min Processes',
                            'Max Tries',
                            'Memory',
                            'Sleep',
                            'Timeout'
                        ];

                $data[]  = [
                    $individualSupervisor->name,
                    $individualSupervisor->pid,
                    $individualSupervisor->status,
                    collect($individualSupervisor->processes)->map(function ($count, $queue) {
                        return $queue.' ('.$count.')';
                    })->implode(', '),
                    $individualSupervisor->options['balance'],
                    $individualSupervisor->options['connection'],
                    $individualSupervisor->options['queue'],
                    $individualSupervisor->options['delay'],
                    $individualSupervisor->options['force'],
                    $individualSupervisor->options['maxProcesses'],
                    $individualSupervisor->options['minProcesses'],
                    $individualSupervisor->options['maxTries'],
                    $individualSupervisor->options['memory'],
                    $individualSupervisor->options['sleep'],
                    $individualSupervisor->options['timeout'],
                ];

                return $this->table($headers, $data);
            }

            return $this->error($individualSupervisorName . ' not found!');
        }

        $this->table([
            'Name', 'PID', 'Status', 'Workers', 'Balancing',
        ], $supervisors->map(function ($supervisor) {
            return [
                $supervisor->name,
                $supervisor->pid,
                $supervisor->status,
                collect($supervisor->processes)->map(function ($count, $queue) {
                    return $queue.' ('.$count.')';
                })->implode(', '),
                $supervisor->options['balance'],
            ];
        })->all());
    }
}
