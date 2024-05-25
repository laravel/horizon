<?php

namespace Laravel\Horizon\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\MasterSupervisor;
use Laravel\Horizon\ProvisioningPlan;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'horizon')]
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
            return $this->components->warn('A master supervisor is already running on this machine.');
        }

        $environment = $this->option('environment') ?? config('horizon.env') ?? config('app.env');

        $master = App::makeWith(MasterSupervisor::class, ['environment' => $environment])
            ->handleOutputUsing(function ($type, $line) {
                $this->output->write($line);
            });

        $provisioningPlan = ProvisioningPlan::get(MasterSupervisor::name());
        $provisioningPlan->deploy($environment);

        $this->components->info('Horizon started successfully.');

        if (!$provisioningPlan->hasEnvironment(($environment))) {
            $this->components->warn("No environment configuration found for the environment: $environment. Check 'environments' on config/horizon.php");
        }

        pcntl_async_signals(true);

        pcntl_signal(SIGINT, function () use ($master) {
            $this->output->writeln('');

            $this->components->info('Shutting down.');

            return $master->terminate();
        });

        $master->monitor();
    }
}
