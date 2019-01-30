<?php

namespace Laravel\Horizon\Console;

use Illuminate\Queue\Console\WorkCommand as BaseWorkCommand;

class WorkCommand extends BaseWorkCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'horizon:work
                            {connection? : The name of the queue connection to work}
                            {--delay=0 : Amount of time to delay failed jobs}
                            {--daemon : Run the worker in daemon mode (Deprecated)}
                            {--force : Force the worker to run even in maintenance mode}
                            {--memory=128 : The memory limit in megabytes}
                            {--once : Only process the next job on the queue}
                            {--stop-when-empty : Stop when the queue is empty}
                            {--queue= : The names of the queues to work}
                            {--sleep=3 : Number of seconds to sleep when no job is available}
                            {--supervisor= : The name of the supervisor the worker belongs to}
                            {--timeout=60 : The number of seconds a child process can run}
                            {--tries=0 : Number of times to attempt a job before logging it failed}';

    /**
     * Indicates whether the command should be shown in the Artisan command list.
     *
     * @var bool
     */
    protected $hidden = true;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (config('horizon.fast_termination')) {
            ignore_user_abort(true);
        }

        parent::handle();
    }
}
