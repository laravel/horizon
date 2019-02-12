<?php

namespace Laravel\Horizon\Console;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Laravel\Horizon\MasterSupervisor;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
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
                            {--wait : Wait for all workers to terminate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Terminate the master supervisor so it can be restarted';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Contracts\Cache\Factory $cache
     * @param  \Laravel\Horizon\Contracts\MasterSupervisorRepository  $masters
     * @return void
     */
    public function handle(CacheFactory $cache, MasterSupervisorRepository $masters)
    {
        if (config('horizon.fast_termination')) {
            $cache->forever(
                'horizon:terminate:wait', $this->option('wait')
            );
        }

        $masters = collect($masters->all())->filter(function ($master) {
            return Str::startsWith($master->name, MasterSupervisor::basename());
        })->all();

        foreach (Arr::pluck($masters, 'pid') as $processId) {
            $this->info("Sending TERM Signal To Process: {$processId}");

            if (! posix_kill($processId, SIGTERM)) {
                $this->error("Failed to kill process: {$processId} (".posix_strerror(posix_get_last_error()).')');
            }
        }

        $this->laravel['cache']->forever('illuminate:queue:restart', $this->currentTime());
    }
}
