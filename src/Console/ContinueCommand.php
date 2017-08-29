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
     * @return void
     */
    public function handle()
    {
        $masters = resolve(MasterSupervisorRepository::class)->all();

        $masters = collect($masters)->filter(function ($master) {
            return Str::startsWith($master->name, MasterSupervisor::basename());
        })->all();

        foreach (array_pluck($masters, 'pid') as $processId) {
            $this->info("Sending CONT Signal To Process: {$processId}");

            posix_kill($processId, SIGCONT);
        }
    }
}
