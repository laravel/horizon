<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;

class TimeoutCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:timeout {environment=production : The environment name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the maximum timeout for the given environment';

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
        $this->line(collect(
            config('horizon.environments.'.$this->argument('environment'), [])
        )->max('timeout') ?? 60);
    }
}
