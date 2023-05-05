<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\ProvisioningPlan;

class HorizonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon {--environment= : The environment name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start a master supervisor in the foreground';

    /**
     * Execute the console command.
     *
     * @param  \Laravel\Horizon\Contracts\MasterSupervisorRepository  $masters
     * @return void
     */
    public function handle(MasterSupervisorRepository $masters)
    {
        if ($masters->find(MasterSupervisor::name())) {
            return $this->comment('A master supervisor is already running on this machine.');
        }

        $master = (new MasterSupervisor)->handleOutputUsing(function ($type, $line) {
            $this->output->write($line);
        });

        ProvisioningPlan::get(MasterSupervisor::name())->deploy(
            $this->option('environment') ?? config('horizon.env') ?? config('app.env')
        );

        $this->info(sprintf('%s Horizon started successfully.', Carbon::now()->format('Y-m-d H:i:s')));

        pcntl_async_signals(true);

        pcntl_signal(SIGINT, function () use ($master) {
            $this->line('Shutting down...');

            return $master->terminate();
        });

        $master->monitor();
    }
}
