<?php

namespace Laravel\Horizon\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Laravel\Horizon\MasterSupervisor;
use Illuminate\Support\InteractsWithTime;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;

class TerminateCommand extends Command
{
    use InteractsWithTime;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:terminate
                            {--wait : Horizon supervisors should wait for their workers to terminate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Terminate the master supervisor so it can be restarted';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (config('horizon.fast_termination')) {
            $this->laravel['cache']->forever('horizon:terminate:wait', $this->option('wait'));
        }

        $masters = app(MasterSupervisorRepository::class)->all();

        $masters = collect($masters)->filter(function ($master) {
            return Str::startsWith($master->name, MasterSupervisor::basename());
        })->all();

        foreach (array_pluck($masters, 'pid') as $processId) {
            $this->info("Sending TERM Signal To Process: {$processId}");

            posix_kill($processId, SIGTERM);
        }

        $this->laravel['cache']->forever('illuminate:queue:restart', $this->currentTime());
    }
}
