<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\MasterSupervisor;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'horizon:continue')]
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
        $masters = collect($masters->all())
            ->filter(fn ($master) => Str::startsWith($master->name, MasterSupervisor::basename()))
            ->all();

        collect(Arr::pluck($masters, 'pid'))
            ->whenNotEmpty(fn () => $this->components->info('Sending CONT signal to processes.'))
            ->whenEmpty(fn () => $this->components->info('No processes to continue.'))
            ->each(function ($processId) {
                $result = true;

                $this->components->task("Process: $processId", function () use ($processId, &$result) {
                    return $result = posix_kill($processId, SIGCONT);
                });

                if (! $result) {
                    $this->components->error("Failed to kill process: {$processId} (".posix_strerror(posix_get_last_error()).')');
                }
            })->whenNotEmpty(fn () => $this->output->writeln(''));
    }
}
