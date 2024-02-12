<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\ProvisioningPlan;

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
        $plan = ProvisioningPlan::get(MasterSupervisor::name())->plan;

        $environment = $this->argument('environment');

        $timeout = collect($plan[$this->argument('environment')] ?? [])->max('timeout') ?? 60;

        $this->components->info('Maximum timeout for '.$environment.' environment: '.$timeout.' seconds.');
    }
}
